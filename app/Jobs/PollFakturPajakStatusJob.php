<?php

namespace App\Jobs;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Polls the PJAP for DJP's verdict on a submitted Faktur Pajak.
 *
 * Chain: SubmitFakturPajakJob → (30s) → this job → (5 min, while pending).
 * After $tries polls the chain simply stops: the faktur stays 'submitted'
 * with pjap_status 'pending' for a manual check, exactly as specified in
 * docs/ROADMAP.md §2.5.
 */
class PollFakturPajakStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 10;
    public int $backoff = 60;   // 1 minute between polls

    public function __construct(public FakturPajak $faktur) {}

    public function handle(): void
    {
        // Queue workers have no HTTP tenant context (see SubmitFakturPajakJob).
        Tenant::find($this->faktur->tenant_id)?->makeCurrent();

        if (! $this->faktur->pjap_submission_id) {
            return;
        }

        $pjap = app(PjapInterface::class);

        $result = $pjap->checkStatus($this->faktur->pjap_submission_id);

        $this->faktur->update([
            'pjap_status'          => $result['status'],
            'djp_response_at'      => now(),
            'djp_response_payload' => $result['raw'],
        ]);

        match ($result['status']) {
            'approved' => $this->faktur->update([
                'status'               => 'approved',
                'djp_reference_number' => $result['djp_reference'],
            ]),
            'rejected' => $this->faktur->update(['status' => 'rejected']),
            'pending'  => $this->reschedulePolling(),
        };
    }

    private function reschedulePolling(): void
    {
        if ($this->attempts() < $this->tries) {
            static::dispatch($this->faktur)
                ->onQueue('tax-submissions')
                ->delay(now()->addMinutes(5));
        }
        // After max polls, leave as 'submitted' with pjap_status 'pending' —
        // the user can check manually.
    }
}
