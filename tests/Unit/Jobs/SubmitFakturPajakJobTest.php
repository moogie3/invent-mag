<?php

namespace Tests\Unit\Jobs;

use App\Jobs\PollFakturPajakStatusJob;
use App\Jobs\SubmitFakturPajakJob;
use App\Models\FakturPajak;
use App\Models\Sales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * SubmitFakturPajakJob — e-Faktur Phase 2.
 *
 * Queue::fake() is MANDATORY in every test: phpunit runs the sync queue,
 * and the success path dispatches PollFakturPajakStatusJob with a delay,
 * which the sync connection would execute immediately (and, while the
 * verdict stays pending, recurse).
 */
class SubmitFakturPajakJobTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    private function makeFaktur(array $attributes = []): FakturPajak
    {
        $sale = Sales::factory()->create();

        return FakturPajak::create(array_merge([
            'sales_id' => $sale->id,
            'nsfp' => '010.000-26.00000001',
            'tanggal_faktur' => '2026-11-04',
            'masa_pajak' => 10,
            'tahun_pajak' => 2026,
            'npwp_penjual' => '01.234.567.8-901.000',
            'nama_penjual' => 'Test Tenant',
            'alamat_penjual' => 'Jakarta',
            'npwp_pembeli' => '02.111.222.3-444.555',
            'nama_pembeli' => 'Customer A',
            'alamat_pembeli' => 'Bandung',
            'dpp' => 100000,
            'ppn' => 11000,
            'ppnbm' => 0,
            'ppn_rate' => 11.00,
            'status' => 'draft',
        ], $attributes));
    }

    #[Test]
    public function it_submits_and_queues_the_status_poll(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response(['code' => '00', 'data' => ['id' => 42, 'approval_status' => 'DRAFT']], 200)]);
        $faktur = $this->makeFaktur();

        (new SubmitFakturPajakJob($faktur))->handle();

        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertSame('42', $faktur->pjap_submission_id);
        $this->assertSame('pending_djp', $faktur->pjap_status);
        $this->assertNotNull($faktur->djp_submitted_at);
        $this->assertEquals(1, $faktur->retry_count);

        Http::assertSent(fn (Request $request) => $request->hasHeader(
            'X-Idempotency-Key',
            'FP-'.$faktur->id.'-'.$faktur->nsfp
        ));
        Queue::assertPushedOn('tax-submissions', PollFakturPajakStatusJob::class);
        Queue::assertPushed(
            PollFakturPajakStatusJob::class,
            fn (PollFakturPajakStatusJob $job) => $job->faktur->is($faktur) && $job->delay !== null
        );
    }

    #[Test]
    public function it_skips_when_the_pjap_already_holds_the_faktur(): void
    {
        Queue::fake();
        Http::fake();
        $faktur = $this->makeFaktur([
            'status' => 'submitted',
            'pjap_submission_id' => 'ALREADY-1',
        ]);

        (new SubmitFakturPajakJob($faktur))->handle();

        Http::assertNothingSent();
        Queue::assertNothingPushed();
        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertSame('ALREADY-1', $faktur->pjap_submission_id);
    }

    #[Test]
    public function it_skips_faktur_that_reached_a_terminal_state(): void
    {
        Queue::fake();
        Http::fake();
        $faktur = $this->makeFaktur(['status' => 'approved']);

        (new SubmitFakturPajakJob($faktur))->handle();

        Http::assertNothingSent();
        Queue::assertNothingPushed();
        $this->assertSame('approved', $faktur->refresh()->status);
    }

    #[Test]
    public function it_records_the_attempt_and_rethrows_on_api_failure(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);
        $faktur = $this->makeFaktur();

        $caught = null;
        try {
            (new SubmitFakturPajakJob($faktur))->handle();
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        // Re-thrown so the queue can retry up to $tries times
        $this->assertNotNull($caught, 'The job must re-throw on PJAP failure');
        $this->assertStringContainsString('Klikpajak API error 500', $caught->getMessage());

        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertSame('error_attempt_1', $faktur->pjap_status);
        $this->assertNull($faktur->pjap_submission_id);

        Http::assertSentCount(3); // retry(3, 1000ms) inside submit()
        Queue::assertNotPushed(PollFakturPajakStatusJob::class);
    }

    #[Test]
    public function it_resubmits_a_faktur_whose_previous_attempt_failed(): void
    {
        // status='submitted' WITHOUT a pjap_submission_id is a failed attempt
        // being retried — the job must fall through and resubmit.
        Queue::fake();
        Http::fake(['*' => Http::response(['code' => '00', 'data' => ['id' => 2, 'approval_status' => 'DRAFT']], 200)]);
        $faktur = $this->makeFaktur([
            'status' => 'submitted',
            'pjap_status' => 'error_attempt_1',
        ]);

        (new SubmitFakturPajakJob($faktur))->handle();

        $faktur->refresh();
        $this->assertSame('2', $faktur->pjap_submission_id);
        $this->assertSame('pending_djp', $faktur->pjap_status);
        Queue::assertPushedOn('tax-submissions', PollFakturPajakStatusJob::class);
    }

    #[Test]
    public function failed_reverts_the_faktur_to_draft_for_manual_retry(): void
    {
        $faktur = $this->makeFaktur([
            'status' => 'submitted',
            'pjap_status' => 'error_attempt_3',
        ]);

        (new SubmitFakturPajakJob($faktur))->failed(new \RuntimeException('retries exhausted'));

        $faktur->refresh();
        $this->assertSame('draft', $faktur->status);
        $this->assertSame('failed_permanently', $faktur->pjap_status);
    }
}
