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
 * Asserted against Mekari's LIVE Klikpajak Public API v1 contract
 * (HMAC signing, /v1/efaktur/out endpoints, {code,message,data} envelope),
 * the HMAC signature is recomputed independently inside the tests so the
 * signing algorithm itself is locked.
 *
 * NOTE: Http::fake() MERGES stubs — the first matching stub wins for the
 * whole test — so every test registers exactly one fake (use a sequence
 * when responses must differ between calls).
 */
class KlikpajakServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const BASE_URL = 'https://sandbox-api.mekari.com/v2/klikpajak';

    private const CLIENT_ID = 'client-123';

    private const CLIENT_SECRET = 'secret-abc';

    private KlikpajakService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $this->service = new KlikpajakService([
            'base_url' => self::BASE_URL,
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'bulk_poll_interval_ms' => 0, // no real sleeps in tests
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

    /**
     * Recompute Mekari's HMAC signature from the request the service actually
     * sent (Date header + METHOD path?query HTTP/1.1) and compare: this
     * locks the signing algorithm, not just header presence.
     */
    private function isSignedForMekari(Request $request, string $method): bool
    {
        $date = $request->header('Date')[0] ?? '';
        $parts = parse_url($request->url());
        $target = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        $payload = implode("\n", ['date: '.$date, $method.' '.$target.' HTTP/1.1']);
        $signature = base64_encode(hash_hmac('sha256', $payload, self::CLIENT_SECRET, true));

        return preg_match('/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} GMT$/', $date) === 1
            && $request->hasHeader('Authorization', sprintf(
                'hmac username="%s", algorithm="hmac-sha256", headers="date request-line", signature="%s"',
                self::CLIENT_ID,
                $signature
            ));
    }

    #[Test]
    public function it_submits_with_hmac_signed_headers_and_the_live_payload(): void
    {
        $faktur = $this->makeFaktur(1);
        Http::fake([
            '*' => Http::response(['code' => '00', 'data' => ['id' => 49, 'approval_status' => 'DRAFT']], 200),
        ]);

        $submissionId = $this->service->submit($faktur);

        $this->assertSame('49', $submissionId);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($faktur) {
            $digest = 'SHA-256='.base64_encode(hash('sha256', $request->body(), true));

            return $request->url() === self::BASE_URL.'/v1/efaktur/out?auto_approval=true&auto_calculate=true'
                && $request->method() === 'POST'
                && $this->isSignedForMekari($request, 'POST')
                && $request->hasHeader('Digest', $digest)
                && $request->hasHeader('X-Idempotency-Key', 'FP-'.$faktur->id.'-'.$faktur->nsfp)
                && $request['client_reference_id'] === 'FP-'.$faktur->id
                && $request['transaction_detail'] === '01'
                && $request['additional_trx_detail'] === '00'
                && $request['document_number'] === null
                && $request['document_date'] === '2026-11-04'
                && $request['reference'] === $faktur->nsfp
                && $request['total_dpp'] == 100000
                && $request['total_ppn'] == 11000
                && $request['total_ppnbm'] == 0
                && $request['customer']['name'] === 'Customer A'
                && $request['customer']['npwp'] === '021112223444555'
                && $request['customer']['address'] === 'Bandung'
                && is_array($request['items']);
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
    public function it_throws_on_a_business_error_code_without_retrying(): void
    {
        $faktur = $this->makeFaktur(3);
        Http::fake(['*' => Http::response([
            'code' => '1000',
            'message' => 'Invalid input value or format',
            'data' => ['customer.name should not be null'],
        ], 200)]);

        $caught = null;
        try {
            $this->service->submit($faktur);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A 200 with an error code must not be treated as success');
        $this->assertStringContainsString('Klikpajak API error 1000: Invalid input value or format', $caught->getMessage());
        Http::assertSentCount(1); // HTTP 200 → no transport retry
    }

    #[Test]
    public function it_throws_when_the_response_has_no_submission_id(): void
    {
        $faktur = $this->makeFaktur(4);
        Http::fake(['*' => Http::response(['code' => '00', 'data' => []], 200)]);

        $caught = null;
        try {
            $this->service->submit($faktur);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A 200 without data.id must not be treated as success');
        $this->assertStringContainsString('missing data.id', $caught->getMessage());
        Http::assertSentCount(1); // successful HTTP status → no retries
    }

    #[Test]
    public function it_normalizes_an_approved_verdict(): void
    {
        Http::fake(['*' => Http::response(['code' => '00', 'data' => [
            'id' => 49,
            'approval_status' => 'APPROVED',
            'invoice_status' => 'NORMAL',
            'document_number' => '000.21.01230043',
            'qr_code' => 'http://svc.efaktur.pajak.go.id/validasi/...',
        ]], 200)]);

        $result = $this->service->checkStatus('49');

        $this->assertSame('approved', $result['status']);
        $this->assertSame('000.21.01230043', $result['djp_reference']);
        $this->assertNull($result['rejection_reason']);
        $this->assertIsArray($result['raw']);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && str_ends_with($request->url(), '/v1/efaktur/out/49')
                && $this->isSignedForMekari($request, 'GET')
                && ! $request->hasHeader('Digest'); // GET carries no body
        });
    }

    #[Test]
    public function it_normalizes_a_draft_verdict_as_pending(): void
    {
        Http::fake(['*' => Http::response(['code' => '00', 'data' => [
            'approval_status' => 'DRAFT',
            'invoice_status' => 'NORMAL',
            'document_number' => '000.21.01230043',
        ]], 200)]);

        $result = $this->service->checkStatus('49');

        $this->assertSame('pending', $result['status']);
        $this->assertNull($result['djp_reference'], 'A draft is not yet a DJP reference');
        $this->assertNull($result['rejection_reason']);
    }

    #[Test]
    public function it_normalizes_a_rejected_verdict_with_its_reason(): void
    {
        Http::fake(['*' => Http::response(['code' => '00', 'data' => [
            'approval_status' => 'REJECTED',
            'invoice_status' => 'NORMAL',
            'error_msg' => 'NPWP pembeli tidak valid',
        ]], 200)]);

        $result = $this->service->checkStatus('49');

        $this->assertSame('rejected', $result['status']);
        $this->assertNull($result['djp_reference']);
        $this->assertSame('NPWP pembeli tidak valid', $result['rejection_reason']);
    }

    #[Test]
    public function it_treats_a_cancelled_invoice_as_a_terminal_rejection(): void
    {
        Http::fake(['*' => Http::response(['code' => '00', 'data' => [
            'approval_status' => 'APPROVED',
            'invoice_status' => 'CANCELLED',
            'document_number' => '000.21.01230043',
        ]], 200)]);

        $result = $this->service->checkStatus('49');

        $this->assertSame('rejected', $result['status'], 'A cancelled invoice must stop the poll chain');
        $this->assertSame('Tax invoice cancelled', $result['rejection_reason']);
    }

    #[Test]
    public function it_treats_unknown_or_missing_approval_as_pending(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '00', 'data' => ['approval_status' => 'IN_PROGRESS', 'invoice_status' => 'NORMAL']], 200)
                ->push(['code' => '00', 'data' => []], 200),
        ]);

        $this->assertSame('pending', $this->service->checkStatus('49')['status']);
        $this->assertSame('pending', $this->service->checkStatus('49')['status']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_throws_when_the_status_check_fails(): void
    {
        Http::fake(['*' => Http::response(['message' => 'unavailable'], 500)]);

        $caught = null;
        try {
            $this->service->checkStatus('49');
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertStringContainsString('Klikpajak status check failed', $caught->getMessage());
        Http::assertSentCount(1); // checkStatus does not retry
    }

    #[Test]
    public function it_throws_when_the_status_endpoint_returns_a_business_error(): void
    {
        Http::fake(['*' => Http::response([
            'code' => '1106',
            'message' => 'Tax invoice (id = 49) is not found for editing/ retrieving/ deletion/ cancellation process',
        ], 200)]);

        $caught = null;
        try {
            $this->service->checkStatus('49');
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A 200 with an error code must not fabricate a verdict');
        $this->assertStringContainsString('1106', $caught->getMessage());
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_reports_a_successful_connection_test(): void
    {
        Http::fake(['*' => Http::response([
            'code' => '00',
            'message' => 'Your request to create a resource is successful',
            'data' => ['efakturQuota' => 200000, 'efakturUsage' => 6118, 'endDate' => '2033-12-31', 'packageName' => 'Enterprise'],
        ], 200)]);

        $this->assertTrue($this->service->testConnection());
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/subscription'));
    }

    #[Test]
    public function it_reports_a_failed_connection_test(): void
    {
        Http::fake(['*' => Http::response(['message' => 'nope'], 500)]);

        $this->assertFalse($this->service->testConnection());
    }

    #[Test]
    public function it_reports_a_failed_connection_test_on_a_business_error(): void
    {
        Http::fake(['*' => Http::response(['code' => 'MAG-R02', 'message' => 'Monthly limit exceeded'], 200)]);

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
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '02', 'data' => ['request_id' => 'RID-1']], 200)
                ->push(['code' => '00', 'data' => [
                    'operation' => 'CREATE',
                    'status' => 'SUCCESS',
                    'request' => [
                        ['id' => 30198, 'client_reference_id' => 'FP-'.$f1->id, 'operation_status' => 'SUCCESS', 'invoice_status' => 'NORMAL', 'approval_status' => 'APPROVED'],
                        ['id' => 30199, 'client_reference_id' => 'FP-'.$f2->id, 'operation_status' => 'SUCCESS', 'invoice_status' => 'NORMAL', 'approval_status' => 'DRAFT'],
                    ],
                ]], 200),
        ]);

        $map = $this->service->submitBatch([$f1->id, $f2->id]);

        $this->assertCount(2, $map);
        $this->assertSame('30198', $map[$f1->id]);
        $this->assertSame('30199', $map[$f2->id]);
        Http::assertSent(function (Request $request) use ($f1, $f2) {
            return str_ends_with($request->url(), '/v1/efaktur/out/bulk?auto_approval=true&auto_calculate=true')
                && $request->method() === 'POST'
                && $this->isSignedForMekari($request, 'POST')
                && count($request['invoices']) === 2
                && collect($request['invoices'])->pluck('client_reference_id')->sort()->values()->all()
                    == collect(['FP-'.$f1->id, 'FP-'.$f2->id])->sort()->values()->all()
                && str_starts_with($request->header('X-Idempotency-Key')[0] ?? '', 'BULK-');
        });
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/efaktur/out/bulk/RID-1'));
    }

    #[Test]
    public function it_polls_the_bulk_endpoint_until_the_entries_are_ready(): void
    {
        $f1 = $this->makeFaktur(1);
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '02', 'data' => ['request_id' => 'RID-2']], 200)
                ->push(['code' => '03', 'message' => 'Your bulk request is still in progress'], 200)
                ->push(['code' => '00', 'data' => ['request' => [
                    ['id' => 30200, 'client_reference_id' => 'FP-'.$f1->id, 'operation_status' => 'SUCCESS'],
                ]]], 200),
        ]);

        $map = $this->service->submitBatch([$f1->id]);

        $this->assertSame(['30200'], array_values($map));
        Http::assertSentCount(3); // create + 2 polls
    }

    #[Test]
    public function it_gives_up_when_the_bulk_request_never_finishes(): void
    {
        $f1 = $this->makeFaktur(1);
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '02', 'data' => ['request_id' => 'RID-3']], 200)
                ->push(['code' => '03', 'message' => 'still in progress'], 200)
                ->push(['code' => '03', 'message' => 'still in progress'], 200),
        ]);

        $service = new KlikpajakService([
            'base_url' => self::BASE_URL,
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'bulk_poll_attempts' => 2,
            'bulk_poll_interval_ms' => 0,
        ]);

        $caught = null;
        try {
            $service->submitBatch([$f1->id]);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertStringContainsString('still in progress after 2 polls', $caught->getMessage());
        Http::assertSentCount(3); // create + 2 polls
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
        Http::fake(['*' => Http::response(['code' => '00', 'data' => []], 200)]);

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
    public function it_surfaces_per_invoice_errors_from_a_failed_bulk(): void
    {
        $f1 = $this->makeFaktur(1);
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '02', 'data' => ['request_id' => 'RID-4']], 200)
                ->push(['code' => '1801', 'message' => 'Your bulk request is finished with failures', 'data' => [
                    'operation' => 'CREATE',
                    'status' => 'FAILED',
                    'request' => [
                        ['id' => 30198, 'client_reference_id' => 'FP-'.$f1->id, 'operation_status' => 'FAILED', 'error_msg' => 'NPWP pembeli tidak valid'],
                    ],
                ]], 200),
        ]);

        $caught = null;
        try {
            $this->service->submitBatch([$f1->id]);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertStringContainsString('NPWP pembeli tidak valid', $caught->getMessage());
    }

    #[Test]
    public function it_reports_whether_a_submission_was_cancelled(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['code' => '04', 'data' => ['id' => 51, 'invoice_status' => 'CANCELLED', 'approval_status' => 'APPROVED']], 200)
                ->push(['code' => '1106', 'message' => 'Tax invoice (id = 51) is not found for editing/ retrieving/ deletion/ cancellation process'], 200),
        ]);

        $this->assertTrue($this->service->cancel('51'));
        $this->assertFalse($this->service->cancel('51'));
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) {
            $emptyDigest = 'SHA-256='.base64_encode(hash('sha256', '', true));

            return $request->method() === 'PUT'
                && str_ends_with($request->url(), '/v1/efaktur/out/51/cancel')
                && $this->isSignedForMekari($request, 'PUT')
                && $request->hasHeader('Digest', $emptyDigest);
        });
    }

    #[Test]
    public function it_throws_when_the_cancel_endpoint_is_unreachable(): void
    {
        // A transport failure is retryable and must surface as an exception:
        // returning false here would make the job's $tries/backoff and
        // failed() unreachable, so a network blip would give up for good.
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);

        $caught = null;
        try {
            $this->service->cancel('51');
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'A 5xx on cancel must throw, not return false');
        $this->assertStringContainsString('Klikpajak cancel failed 500', $caught->getMessage());
    }
}
