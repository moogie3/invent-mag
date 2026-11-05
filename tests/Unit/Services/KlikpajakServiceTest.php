<?php

namespace Tests\Unit\Services;

use App\Models\FakturPajak;
use App\Models\Sales;
use App\Services\Pjap\KlikpajakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * Unit tests for the Klikpajak PJAP client (e-Faktur Phase 2).
 *
 * NOTE: Http::fake() MERGES stubs — the first matching stub wins for the
 * whole test — so every test registers exactly one fake (use a sequence
 * when responses must differ between calls).
 */
class KlikpajakServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private KlikpajakService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $this->service = new KlikpajakService([
            'base_url'   => 'https://api-sandbox.klikpajak.id',
            'api_key'    => 'test-key',
            'api_secret' => 'test-secret',
            'npwp'       => '01.234.567.8-901.000',
        ]);
    }

    private function makeFaktur(int $seq = 1): FakturPajak
    {
        $sale = Sales::factory()->create();

        return FakturPajak::create([
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
        ]);
    }

    #[Test]
    public function it_submits_with_idempotency_key_auth_headers_and_full_payload(): void
    {
        $faktur = $this->makeFaktur(1);
        Http::fake([
            '*' => Http::response(['data' => ['submission_id' => 'SUB-123']], 200),
        ]);

        $submissionId = $this->service->submit($faktur);

        $this->assertSame('SUB-123', $submissionId);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($faktur) {
            return $request->url() === 'https://api-sandbox.klikpajak.id/v1/faktur/upload'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request->hasHeader('X-API-Secret', 'test-secret')
                && $request->hasHeader('X-Idempotency-Key', 'FP-'.$faktur->id.'-'.$faktur->nsfp)
                && $request['kd_jenis_transaksi'] === '01'
                && $request['fg_pengganti'] === '0'
                && $request['nomor_faktur'] === $faktur->nsfp
                && $request['masa_pajak'] == 10
                && $request['tahun_pajak'] == 2026
                && $request['tanggal_faktur'] === '2026-11-04'
                && $request['npwp_penjual'] === '01.234.567.8-901.000'
                && $request['npwp_pembeli'] === '02.111.222.3-444.555'
                && $request['jumlah_dpp'] == 100000
                && $request['jumlah_ppn'] == 11000
                && $request['jumlah_ppnbm'] == 0
                && $request['detail'] === [];
        });
    }

    #[Test]
    public function it_throws_when_the_api_returns_an_error_status(): void
    {
        $faktur = $this->makeFaktur(2);
        Http::fake(['*' => Http::response(['message' => 'server exploded'], 500)]);

        $caught = null;
        try {
            $this->service->submit($faktur);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A 500 from the PJAP must surface as a RuntimeException');
        $this->assertStringContainsString('Klikpajak API error 500', $caught->getMessage());
        // submit() retries 3 times before giving up (2 sleeps of 1s each)
        Http::assertSentCount(3);
    }

    #[Test]
    public function it_throws_when_the_response_has_no_submission_id(): void
    {
        $faktur = $this->makeFaktur(3);
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $caught = null;
        try {
            $this->service->submit($faktur);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A 200 without submission_id must not be treated as success');
        $this->assertStringContainsString('missing submission_id', $caught->getMessage());
        Http::assertSentCount(1); // successful HTTP status → no retries
    }

    #[Test]
    public function it_normalizes_an_approved_verdict(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            'status'         => 'APPROVED',
            'nomor_faktur_djp' => '010.000-26.00000099',
            'keterangan'     => null,
        ]], 200)]);

        $result = $this->service->checkStatus('SUB-7');

        $this->assertSame('approved', $result['status']);
        $this->assertSame('010.000-26.00000099', $result['djp_reference']);
        $this->assertNull($result['rejection_reason']);
        $this->assertIsArray($result['raw']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/faktur/status/SUB-7'));
    }

    #[Test]
    public function it_normalizes_a_rejected_verdict_with_its_reason(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            'status'         => 'DITOLAK',
            'nomor_faktur_djp' => null,
            'keterangan'     => 'NPWP pembeli tidak valid',
        ]], 200)]);

        $result = $this->service->checkStatus('SUB-7');

        $this->assertSame('rejected', $result['status']);
        $this->assertNull($result['djp_reference']);
        $this->assertSame('NPWP pembeli tidak valid', $result['rejection_reason']);
    }

    #[Test]
    public function it_treats_an_unknown_or_missing_verdict_as_pending(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => ['status' => 'MENUNGGU']], 200)
                ->push(['data' => []], 200),
        ]);

        $this->assertSame('pending', $this->service->checkStatus('SUB-7')['status']);
        $this->assertSame('pending', $this->service->checkStatus('SUB-7')['status']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_throws_when_the_status_check_fails(): void
    {
        Http::fake(['*' => Http::response(['message' => 'unavailable'], 500)]);

        $caught = null;
        try {
            $this->service->checkStatus('SUB-7');
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertStringContainsString('Klikpajak status check failed', $caught->getMessage());
        Http::assertSentCount(1); // checkStatus does not retry
    }

    #[Test]
    public function it_reports_a_successful_connection_test(): void
    {
        Http::fake(['*' => Http::response(['pong' => true], 200)]);

        $this->assertTrue($this->service->testConnection());
    }

    #[Test]
    public function it_reports_a_failed_connection_test(): void
    {
        Http::fake(['*' => Http::response(['message' => 'nope'], 500)]);

        $this->assertFalse($this->service->testConnection());
    }

    #[Test]
    public function it_reports_a_failed_connection_test_when_the_host_is_unreachable(): void
    {
        Http::fake(['*' => Http::failedConnection('cURL error 6: Could not resolve host')]);

        $this->assertFalse($this->service->testConnection());
    }

    #[Test]
    public function it_maps_batch_submission_results_by_faktur(): void
    {
        $f1 = $this->makeFaktur(1);
        $f2 = $this->makeFaktur(2);
        Http::fake(['*' => Http::response(['data' => [
            ['faktur_id' => $f1->id, 'submission_id' => 'BATCH-1'],
            ['faktur_id' => $f2->id, 'submission_id' => 'BATCH-2'],
        ]], 200)]);

        $map = $this->service->submitBatch([$f1->id, $f2->id]);

        $this->assertCount(2, $map);
        $this->assertSame('BATCH-1', $map[$f1->id]);
        $this->assertSame('BATCH-2', $map[$f2->id]);
        Http::assertSent(function (Request $request) use ($f1, $f2) {
            return str_ends_with($request->url(), '/v1/faktur/upload-batch')
                && count($request['fakturs']) === 2
                && collect($request['fakturs'])->pluck('nomor_faktur')->sort()->values()->all()
                    == collect([$f1->nsfp, $f2->nsfp])->sort()->values()->all();
        });
    }

    #[Test]
    public function it_throws_when_the_batch_endpoint_fails(): void
    {
        $faktur = $this->makeFaktur(1);
        Http::fake(['*' => Http::response(['message' => 'gateway down'], 500)]);

        $caught = null;
        try {
            $this->service->submitBatch([$faktur->id]);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertStringContainsString('Batch submission failed', $caught->getMessage());
        Http::assertSentCount(1); // submitBatch does not retry
    }

    #[Test]
    public function it_throws_when_the_batch_response_is_malformed(): void
    {
        $faktur = $this->makeFaktur(1);
        Http::fake(['*' => Http::response(['data' => 'not-an-array'], 200)]);

        $caught = null;
        try {
            $this->service->submitBatch([$faktur->id]);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertStringContainsString('Batch submission response malformed', $caught->getMessage());
    }

    #[Test]
    public function it_reports_whether_a_submission_was_cancelled(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['ok' => true], 200)
                ->push(['message' => 'not found'], 404),
        ]);

        $this->assertTrue($this->service->cancel('SUB-1'));
        $this->assertFalse($this->service->cancel('SUB-404'));
        Http::assertSentCount(2);
    }
}
