<?php

namespace App\Jobs;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Submits many draft FakturPajak to DJP through ONE PJAP bulk call.
 *
 * The bulk endpoint is the reason this exists: submitting N fakturs one by
 * one costs N requests and N idempotency keys, while submitBatch() sends a
 * single request keyed on the whole id set (roadmap 2.8).
 *
 * Idempotency mirrors SubmitFakturPajakJob: the faktur is marked submitted
 * BEFORE the transport call, so a retry after a lost response picks the same
 * rows up again under the same X-Idempotency-Key instead of skipping them.
 */
class SubmitFakturPajakBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 300;   // 5 minutes between retries
    public int $timeout = 300;   // bulk transport polls DJP for up to ~2 minutes

    /**
     * @param  array<int, int>  $fakturIds
     */
    public function __construct(
        public array $fakturIds,
        public int $tenantId,
    ) {}

    /**
     * One bulk call at a time: the idempotency key covers the whole id set,
     * so two overlapping batches could not be told apart safely.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('faktur-batch')];
    }

    public function handle(): void
    {
        // Queue workers have no HTTP tenant context (see SubmitFakturPajakJob).
        Tenant::find($this->tenantId)?->makeCurrent();

        // Only rows still awaiting a PJAP id. A row already carrying one has
        // been sent; anything past 'submitted' is terminal. The tenant scope
        // on FakturPajak keeps another tenant's ids out even if passed in.
        $ids = FakturPajak::whereIn('id', $this->fakturIds)
            ->whereNull('pjap_submission_id')
            ->whereIn('status', ['draft', 'submitted'])
            ->pluck('id')
            ->all();

        if ($ids === []) {
            Log::warning('FakturPajak batch has nothing to submit', [
                'faktur_ids' => $this->fakturIds,
            ]);

            return;
        }

        // Mark first so a retry falls through the guard above and resubmits
        // under the same idempotency key (see class docblock).
        FakturPajak::whereIn('id', $ids)->update([
            'status'           => 'submitted',
            'djp_submitted_at' => now(),
            'retry_count'      => $this->attempts(),
            'last_retry_at'    => now(),
        ]);

        $pjap = app(PjapInterface::class);

        try {
            $map = $pjap->submitBatch($ids);
        } catch (\Exception $e) {
            Log::error('FakturPajak batch submission failed', [
                'faktur_ids' => $ids,
                'attempt'    => $this->attempts(),
                'error'      => $e->getMessage(),
            ]);

            throw $e; // re-throw -> retry up to $tries, then failed() reverts
        }

        foreach ($map as $fakturId => $pjapSubmissionId) {
            $faktur = FakturPajak::find($fakturId);

            if (! $faktur) {
                continue;
            }

            $faktur->update([
                'pjap_submission_id' => $pjapSubmissionId,
                'pjap_status'        => 'pending_djp',
            ]);

            // Same chain as the single submit: DJP needs a moment to process.
            PollFakturPajakStatusJob::dispatch($faktur)
                ->onQueue('tax-submissions')
                ->delay(now()->addSeconds(30));
        }
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted: revert whatever never reached DJP so the
        // user can select and retry the batch by hand.
        Tenant::find($this->tenantId)?->makeCurrent();

        FakturPajak::whereIn('id', $this->fakturIds)
            ->whereNull('pjap_submission_id')
            ->update([
                'status'      => 'draft',
                'pjap_status' => 'failed_permanently',
            ]);

        Log::critical('FakturPajak batch permanently failed', [
            'faktur_ids' => $this->fakturIds,
            'error'      => $exception->getMessage(),
        ]);
    }
}
