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
 * Submits one Faktur Pajak to DJP through the PJAP (Klikpajak).
 *
 * Runs on the dedicated `tax-submissions` queue so slow DJP calls
 * (2–10s each) never starve other background work.
 *
 * Idempotency (roadmap §2.8): WithoutOverlapping per faktur + the job's
 * own pjap_submission_id guard + X-Idempotency-Key on the HTTP call.
 */
class SubmitFakturPajakJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 300;   // 5 minutes between retries
    public int $timeout = 60;    // 60 seconds per job

    public function __construct(public FakturPajak $faktur) {}

    /**
     * Prevent duplicate jobs for the same FakturPajak.
     * If a submission job is already queued/running for this faktur, skip.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('faktur-'.$this->faktur->id)];
    }

    public function handle(): void
    {
        // Queue workers have no HTTP tenant context — establish it before
        // resolving the per-tenant PJAP binding (method injection would run
        // too early: the container resolves PjapInterface before handle()).
        Tenant::find($this->faktur->tenant_id)?->makeCurrent();
        $pjap = app(PjapInterface::class);

        // Skip when the PJAP already holds this faktur, or when it reached a
        // terminal state. NOTE: a status='submitted' row WITHOUT a
        // pjap_submission_id is a failed attempt being retried — it must
        // fall through and resubmit (the X-Idempotency-Key makes the repeat
        // safe). Guarding on isSubmittable() alone would swallow every retry.
        if ($this->faktur->pjap_submission_id !== null
            || ! in_array($this->faktur->status, ['draft', 'submitted'], true)) {
            Log::warning('FakturPajak not submittable, skipping', ['faktur_id' => $this->faktur->id]);

            return;
        }

        $this->faktur->update([
            'status'           => 'submitted',
            'djp_submitted_at' => now(),
            'retry_count'      => $this->attempts(),
            'last_retry_at'    => now(),
        ]);

        try {
            $pjapSubmissionId = $pjap->submit($this->faktur);

            $this->faktur->update([
                'pjap_submission_id' => $pjapSubmissionId,
                'pjap_status'        => 'pending_djp',
            ]);

            // Start polling after 30 seconds (DJP processing time)
            PollFakturPajakStatusJob::dispatch($this->faktur)
                ->onQueue('tax-submissions')
                ->delay(now()->addSeconds(30));
        } catch (\Exception $e) {
            Log::error('FakturPajak submission failed', [
                'faktur_id' => $this->faktur->id,
                'attempt'   => $this->attempts(),
                'error'     => $e->getMessage(),
            ]);

            $this->faktur->update(['pjap_status' => 'error_attempt_'.$this->attempts()]);

            throw $e; // re-throw → Laravel will retry up to $tries times
        }
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted — revert to draft so the user can retry manually
        $this->faktur->update([
            'status'      => 'draft',
            'pjap_status' => 'failed_permanently',
        ]);

        Log::critical('FakturPajak permanently failed', [
            'faktur_id' => $this->faktur->id,
            'error'     => $exception->getMessage(),
        ]);
    }
}
