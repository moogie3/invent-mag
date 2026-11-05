<?php

namespace Tests\Unit\Jobs;

use App\Jobs\PollFakturPajakStatusJob;
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
 * PollFakturPajakStatusJob — e-Faktur Phase 2.
 *
 * Queue::fake() is set up for every test: the "still pending" branch
 * re-dispatches itself with a 5-minute delay, which the sync queue used
 * by phpunit would run immediately and forever.
 */
class PollFakturPajakStatusJobTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        Queue::fake();
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
            'status' => 'submitted',
        ], $attributes));
    }

    #[Test]
    public function it_marks_the_faktur_approved_with_the_djp_reference(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            'status'           => 'APPROVED',
            'nomor_faktur_djp' => '010.000-26.00000099',
            'keterangan'       => null,
        ]], 200)]);
        $faktur = $this->makeFaktur(['pjap_submission_id' => 'SUB-7']);

        (new PollFakturPajakStatusJob($faktur))->handle();

        $faktur->refresh();
        $this->assertSame('approved', $faktur->status);
        $this->assertSame('approved', $faktur->pjap_status);
        $this->assertSame('010.000-26.00000099', $faktur->djp_reference_number);
        $this->assertNotNull($faktur->djp_response_at);
        $this->assertIsArray($faktur->djp_response_payload);

        Queue::assertNothingPushed(); // verdict in → chain stops
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/faktur/status/SUB-7'));
    }

    #[Test]
    public function it_marks_the_faktur_rejected_with_the_djp_reason(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            'status'           => 'DITOLAK',
            'nomor_faktur_djp' => null,
            'keterangan'       => 'NPWP pembeli tidak valid',
        ]], 200)]);
        $faktur = $this->makeFaktur(['pjap_submission_id' => 'SUB-7']);

        (new PollFakturPajakStatusJob($faktur))->handle();

        $faktur->refresh();
        $this->assertSame('rejected', $faktur->status);
        $this->assertSame('rejected', $faktur->pjap_status);
        $this->assertNull($faktur->djp_reference_number);
        $this->assertSame(
            'NPWP pembeli tidak valid',
            $faktur->djp_response_payload['data']['keterangan'] ?? null
        );
        Queue::assertNothingPushed(); // rejection is terminal — no re-poll
    }

    #[Test]
    public function it_reschedules_polling_while_the_verdict_is_pending(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            'status' => 'PENDING',
        ]], 200)]);
        $faktur = $this->makeFaktur(['pjap_submission_id' => 'SUB-7']);

        (new PollFakturPajakStatusJob($faktur))->handle();

        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status); // untouched
        $this->assertSame('pending', $faktur->pjap_status);

        Queue::assertPushedOn('tax-submissions', PollFakturPajakStatusJob::class);
        Queue::assertPushed(
            PollFakturPajakStatusJob::class,
            fn (PollFakturPajakStatusJob $job) => $job->faktur->is($faktur) && $job->delay !== null
        );
    }

    #[Test]
    public function it_treats_a_missing_verdict_as_pending_and_reschedules(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);
        $faktur = $this->makeFaktur(['pjap_submission_id' => 'SUB-7']);

        (new PollFakturPajakStatusJob($faktur))->handle();

        $this->assertSame('pending', $faktur->refresh()->pjap_status);
        Queue::assertPushedOn('tax-submissions', PollFakturPajakStatusJob::class);
    }

    #[Test]
    public function it_returns_silently_when_no_pjap_submission_exists(): void
    {
        Http::fake();
        $faktur = $this->makeFaktur(); // pjap_submission_id stays null

        (new PollFakturPajakStatusJob($faktur))->handle();

        Http::assertNothingSent();
        Queue::assertNothingPushed();
        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertNull($faktur->pjap_status);
        $this->assertNull($faktur->djp_response_at);
    }

    #[Test]
    public function it_rethrows_when_the_status_check_fails(): void
    {
        Http::fake(['*' => Http::response(['message' => 'unavailable'], 500)]);
        $faktur = $this->makeFaktur(['pjap_submission_id' => 'SUB-7']);

        $caught = null;
        try {
            (new PollFakturPajakStatusJob($faktur))->handle();
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A failed status check must not corrupt the faktur');
        $this->assertStringContainsString('Klikpajak status check failed', $caught->getMessage());

        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertNull($faktur->djp_response_at);
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    }
}
