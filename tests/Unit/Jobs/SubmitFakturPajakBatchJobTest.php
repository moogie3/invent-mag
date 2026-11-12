<?php

namespace Tests\Unit\Jobs;

use App\Jobs\PollFakturPajakStatusJob;
use App\Jobs\SubmitFakturPajakBatchJob;
use App\Models\FakturPajak;
use App\Models\Sales;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * SubmitFakturPajakBatchJob: the bulk half of e-Faktur Phase 2.
 *
 * Queue::fake() is MANDATORY in every test: phpunit runs the sync queue,
 * and the success path dispatches PollFakturPajakStatusJob with a delay,
 * which the sync connection would execute immediately.
 *
 * Http::fake() MERGES stubs and the first matching stub wins for the whole
 * test, so each test registers exactly one fake (use a sequence when the
 * responses must differ between the create call and the poll).
 */
class SubmitFakturPajakBatchJobTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    private function makeFaktur(int $seq, array $attributes = []): FakturPajak
    {
        $sale = Sales::factory()->create();

        return FakturPajak::create(array_merge([
            'sales_id' => $sale->id,
            'nsfp' => sprintf('010.000-26.%08d', $seq),
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

    /**
     * Bulk create ack (code 02) followed by the finished poll (code 00).
     *
     * @param  array<int, array{id: int, pjap: string}>  $entries
     */
    private function fakeBulkSuccess(array $entries): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '02', 'data' => ['request_id' => 'RID-B1']], 200)
                ->push(['code' => '00', 'data' => [
                    'operation' => 'CREATE',
                    'status' => 'SUCCESS',
                    'request' => array_map(fn (array $e) => [
                        'id' => $e['pjap'],
                        'client_reference_id' => 'FP-'.$e['id'],
                        'operation_status' => 'SUCCESS',
                        'invoice_status' => 'NORMAL',
                        'approval_status' => 'DRAFT',
                    ], $entries),
                ]], 200),
        ]);
    }

    #[Test]
    public function it_submits_every_eligible_row_and_starts_the_poll_chain(): void
    {
        Queue::fake();
        $f1 = $this->makeFaktur(1);
        $f2 = $this->makeFaktur(2);
        $this->fakeBulkSuccess([
            ['id' => $f1->id, 'pjap' => '30198'],
            ['id' => $f2->id, 'pjap' => '30199'],
        ]);

        (new SubmitFakturPajakBatchJob([$f1->id, $f2->id], $this->tenant->id))->handle();

        $f1->refresh();
        $f2->refresh();
        $this->assertSame('submitted', $f1->status);
        $this->assertSame('30198', $f1->pjap_submission_id);
        $this->assertSame('pending_djp', $f1->pjap_status);
        $this->assertNotNull($f1->djp_submitted_at);
        $this->assertSame('30199', $f2->pjap_submission_id);

        Queue::assertPushedOn('tax-submissions', PollFakturPajakStatusJob::class);
        Queue::assertPushedTimes(PollFakturPajakStatusJob::class, 2); // one poll per faktur

        // The bulk idempotency key must cover the whole set, not each row.
        Http::assertSent(fn (Request $r) => str_starts_with($r->header('X-Idempotency-Key')[0] ?? '', 'BULK-'));
    }

    #[Test]
    public function it_ignores_rows_djp_already_holds_and_terminal_rows(): void
    {
        Queue::fake();
        $sent = $this->makeFaktur(1, ['pjap_submission_id' => 'ALREADY-1', 'status' => 'submitted']);
        $terminal = $this->makeFaktur(2, ['status' => 'approved']);
        $fresh = $this->makeFaktur(3);

        $this->fakeBulkSuccess([['id' => $fresh->id, 'pjap' => '30200']]);

        (new SubmitFakturPajakBatchJob(
            [$sent->id, $terminal->id, $fresh->id],
            $this->tenant->id
        ))->handle();

        // The already-sent row keeps its id; the terminal row is untouched.
        $this->assertSame('ALREADY-1', $sent->refresh()->pjap_submission_id);
        $this->assertSame('approved', $terminal->refresh()->status);
        $this->assertNull($terminal->pjap_submission_id);

        // Only the fresh row reached the poll chain.
        Queue::assertPushedTimes(PollFakturPajakStatusJob::class, 1);
        Queue::assertPushed(
            PollFakturPajakStatusJob::class,
            fn (PollFakturPajakStatusJob $job) => $job->faktur->is($fresh)
        );
    }

    #[Test]
    public function it_sends_nothing_when_no_row_is_eligible(): void
    {
        Queue::fake();
        Http::fake();
        $sent = $this->makeFaktur(1, ['pjap_submission_id' => 'ALREADY-1', 'status' => 'submitted']);

        (new SubmitFakturPajakBatchJob([$sent->id], $this->tenant->id))->handle();

        Http::assertNothingSent();
        Queue::assertNothingPushed();
        $this->assertSame('ALREADY-1', $sent->refresh()->pjap_submission_id);
    }

    #[Test]
    public function it_marks_rows_before_the_call_so_a_retry_resubmits(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);
        $faktur = $this->makeFaktur(1);

        $caught = null;
        try {
            (new SubmitFakturPajakBatchJob([$faktur->id], $this->tenant->id))->handle();
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        // Re-thrown so the queue can retry up to $tries times.
        $this->assertNotNull($caught, 'The job must re-throw on PJAP failure');

        $faktur->refresh();
        // Marked first: the retry guard sees status='submitted' with no PJAP
        // id and falls through instead of skipping the row forever.
        $this->assertSame('submitted', $faktur->status);
        $this->assertNull($faktur->pjap_submission_id);
        Queue::assertNotPushed(PollFakturPajakStatusJob::class);
    }

    #[Test]
    public function failed_reverts_unsent_rows_to_draft_for_a_manual_retry(): void
    {
        $unsent = $this->makeFaktur(1, ['status' => 'submitted']);
        $delivered = $this->makeFaktur(2, [
            'status' => 'submitted',
            'pjap_submission_id' => '30198',
        ]);

        (new SubmitFakturPajakBatchJob([$unsent->id, $delivered->id], $this->tenant->id))
            ->failed(new \RuntimeException('retries exhausted'));

        // Only the row that never reached DJP is rolled back; the delivered
        // one keeps its PJAP id or DJP would hold an untracked faktur.
        $this->assertSame('draft', $unsent->refresh()->status);
        $this->assertSame('failed_permanently', $unsent->pjap_status);
        $this->assertSame('30198', $delivered->refresh()->pjap_submission_id);
        $this->assertSame('submitted', $delivered->status);
    }

    #[Test]
    public function it_never_reaches_another_tenants_rows(): void
    {
        Queue::fake();
        Http::fake();
        $mine = $this->makeFaktur(1);

        // A forged id list from another tenant must not travel: the job
        // makes that tenant current, the scope on FakturPajak drops my row,
        // so the batch collapses to nothing instead of crossing tenants.
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);

        (new SubmitFakturPajakBatchJob([$mine->id, 999999], $otherTenant->id))->handle();

        Http::assertNothingSent();
        Queue::assertNothingPushed();

        // Back to my tenant before asserting: refresh() is tenant-scoped too.
        $this->tenant->makeCurrent();
        $this->assertNull($mine->refresh()->pjap_submission_id);
        $this->assertSame('draft', $mine->status);
    }
}
