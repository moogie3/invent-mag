<?php

namespace App\Jobs;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use App\Models\Tenant;
use App\Services\EFakturService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Voids a Faktur Pajak already accepted by DJP (Faktur Batal) via the PJAP.
 *
 * Distinct from EFakturService::cancelFakturPajak(), which is the Phase 1
 * LOCAL cancel of a draft that never left this app. This job is for a faktur
 * carrying a pjap_submission_id: DJP must be told first, and only a confirmed
 * cancel is written back locally.
 *
 * Queued for the same reason SubmitFakturPajakJob is: Klikpajak calls take
 * seconds and must not hold the request open.
 */
class CancelFakturPajakJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;    // 1 minute between retries
    public int $timeout = 60;

    public function __construct(public FakturPajak $faktur) {}

    /**
     * One cancel at a time per faktur: a retry racing the first attempt
     * would double-void at DJP.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('faktur-cancel-'.$this->faktur->id)];
    }

    public function handle(): void
    {
        // Queue workers have no HTTP tenant context (see SubmitFakturPajakJob).
        Tenant::find($this->faktur->tenant_id)?->makeCurrent();

        if (! $this->faktur->pjap_submission_id) {
            Log::warning('FakturPajak has no PJAP submission to cancel', [
                'faktur_id' => $this->faktur->id,
            ]);

            return;
        }

        // Already voided locally: nothing left to tell DJP about.
        if ($this->faktur->status === 'cancelled') {
            return;
        }

        $pjap = app(PjapInterface::class);

        try {
            $cancelled = $pjap->cancel($this->faktur->pjap_submission_id);
        } catch (\Exception $e) {
            Log::error('FakturPajak DJP cancel failed', [
                'faktur_id' => $this->faktur->id,
                'attempt'   => $this->attempts(),
                'error'     => $e->getMessage(),
            ]);

            throw $e; // re-throw -> retry up to $tries, then failed()
        }

        if (! $cancelled) {
            // DJP answered but refused (wrong state, unknown id, ...). A
            // retry cannot change that answer, so log and stop cleanly
            // instead of burning the retry budget.
            Log::error('FakturPajak DJP cancel rejected', [
                'faktur_id' => $this->faktur->id,
            ]);

            return;
        }

        // DJP confirmed the void: mirror it locally in one transaction.
        app(EFakturService::class)->cancelSubmittedFakturPajak($this->faktur);

        Log::info('FakturPajak cancelled at DJP', [
            'faktur_id' => $this->faktur->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Transport kept failing. Leave the faktur as-is (still 'submitted'):
        // DJP may or may not have voided it, so rewriting local state here
        // could lie. The user can retry from the UI.
        Log::critical('FakturPajak DJP cancel permanently failed', [
            'faktur_id' => $this->faktur->id,
            'error'     => $exception->getMessage(),
        ]);
    }
}
