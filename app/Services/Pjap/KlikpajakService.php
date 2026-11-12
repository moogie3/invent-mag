<?php

namespace App\Services\Pjap;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mekari Klikpajak PJAP client — the concrete transport behind the
 * PjapInterface contract (Phase 2 of e-Faktur: direct DJP submission,
 * no manual CSV / desktop app).
 *
 * Reconciled against Mekari's LIVE Klikpajak Public API v1 on 2026-11-05
 * (developers.mekari.com HMAC spec + the published Postman collection):
 *  - auth is HMAC-SHA256 request signing with clientId/clientSecret:
 *    there is no Bearer token and no X-API-Secret header;
 *  - endpoints live under {base}/v1/efaktur/out... on mekari.com hosts;
 *  - every response is a {code, message, data} envelope ("00" ok,
 *    "02" bulk accepted, "03" bulk still processing, "04" approve/cancel ok,
 *    "1801" bulk finished with failures).
 *
 * Every mutating call carries X-Idempotency-Key so a retried job can never
 * double-submit a faktur to DJP (idempotency layer 2 of 5 — see
 * docs/ROADMAP.md §2.8).
 */
class KlikpajakService implements PjapInterface
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private int $bulkPollAttempts;

    private int $bulkPollIntervalMs;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->bulkPollAttempts = (int) ($config['bulk_poll_attempts'] ?? 10);
        $this->bulkPollIntervalMs = (int) ($config['bulk_poll_interval_ms'] ?? 1000);
    }

    public function submit(FakturPajak $faktur): string
    {
        // auto_approval=true: DJP approval is requested up-front so the poll
        // chain can reach APPROVED instead of parking on DRAFT forever.
        $url = $this->baseUrl.'/v1/efaktur/out?auto_approval=true&auto_calculate=true';
        $body = json_encode($this->buildPayload($faktur));

        $response = Http::withHeaders($this->headers('POST', $url, $body))
            ->withBody($body, 'application/json')
            ->timeout(30)
            // Idempotency key prevents duplicate submissions even if the request is retried
            ->withHeaders(['X-Idempotency-Key' => 'FP-'.$faktur->id.'-'.$faktur->nsfp])
            ->retry(3, sleepMilliseconds: 1000, throw: false)
            ->post($url);

        $json = $this->envelope($response, ['00'], 'Klikpajak API error', ['faktur_id' => $faktur->id]);

        $submissionId = $json['data']['id'] ?? null;

        if ($submissionId === null || $submissionId === '') {
            throw new \RuntimeException('Klikpajak submit response missing data.id: '.$response->body());
        }

        return (string) $submissionId;
    }

    public function submitBatch(array $fakturIds): array
    {
        $fakturs = FakturPajak::with('items')->whereIn('id', $fakturIds)->get();

        if ($fakturs->isEmpty()) {
            return [];
        }

        $invoices = $fakturs->map(fn ($f) => $this->buildPayload($f))->values()->all();
        $body = json_encode(['invoices' => $invoices]);
        $url = $this->baseUrl.'/v1/efaktur/out/bulk?auto_approval=true&auto_calculate=true';

        $response = Http::withHeaders($this->headers('POST', $url, $body))
            ->withBody($body, 'application/json')
            ->timeout(120)
            ->withHeaders([
                'X-Idempotency-Key' => 'BULK-'.sha1($fakturs->pluck('id')->sort()->values()->implode(',')),
            ])
            ->post($url);

        $json = $this->envelope($response, ['00', '02'], 'Batch submission failed');
        $requestId = $json['data']['request_id'] ?? null;

        if (! is_string($requestId) || $requestId === '') {
            throw new \RuntimeException('Batch submission response malformed (missing data.request_id): '.$response->body());
        }

        // The bulk endpoint is asynchronous: it acks with a request_id, and
        // the per-invoice PJAP ids only appear on a later status poll.
        $entries = $this->awaitBulkEntries($requestId);

        $map = [];
        $failed = [];

        foreach ($entries as $entry) {
            $ref = (string) ($entry['client_reference_id'] ?? '');

            if (! preg_match('/^FP-(\d+)$/', $ref, $matches)) {
                continue;
            }

            $fakturId = (int) $matches[1];

            if (($entry['operation_status'] ?? '') === 'FAILED') {
                $failed[] = 'FP-'.$fakturId.': '.($entry['error_msg'] ?? 'unknown error');

                continue;
            }

            $map[$fakturId] = (string) ($entry['id'] ?? '');
        }

        if ($failed !== []) {
            throw new \RuntimeException('Batch submission failed: '.implode('; ', $failed));
        }

        $missing = $fakturs->pluck('id')
            ->reject(fn ($id) => isset($map[$id]) && $map[$id] !== '')
            ->values();

        if ($missing->isNotEmpty()) {
            throw new \RuntimeException(
                'Batch submission incomplete. No PJAP id returned for faktur(s): '.$missing->implode(', ')
            );
        }

        return $map;
    }

    public function checkStatus(string $pjapSubmissionId): array
    {
        $url = $this->baseUrl.'/v1/efaktur/out/'.rawurlencode($pjapSubmissionId);

        $response = Http::withHeaders($this->headers('GET', $url))
            ->timeout(15)
            ->get($url);

        $json = $this->envelope($response, ['00'], 'Klikpajak status check failed');
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];

        $invoiceStatus = strtoupper((string) ($data['invoice_status'] ?? ''));
        $approval = strtoupper((string) ($data['approval_status'] ?? ''));

        // A cancelled/deleted tax invoice can never become approved: make it
        // terminal so the poll chain stops instead of spinning until $tries.
        if (in_array($invoiceStatus, ['CANCELLED', 'DELETED'], true)) {
            return [
                'status' => 'rejected',
                'djp_reference' => null,
                'rejection_reason' => 'Tax invoice '.strtolower($invoiceStatus),
                'raw' => $json,
            ];
        }

        return [
            'status' => $this->normalizeStatus($approval),
            'djp_reference' => $approval === 'APPROVED' ? ($data['document_number'] ?? null) : null,
            'rejection_reason' => $approval === 'REJECTED' ? ($data['error_msg'] ?? null) : null,
            'raw' => $json,
        ];
    }

    public function cancel(string $pjapSubmissionId): bool
    {
        $url = $this->baseUrl.'/v1/efaktur/out/'.rawurlencode($pjapSubmissionId).'/cancel';

        $response = Http::withHeaders($this->headers('PUT', $url, ''))
            ->withBody('', 'application/json')
            ->timeout(15)
            ->put($url);

        // Transport failure (5xx, timeout, DNS) is retryable and must reach
        // the caller as an exception, otherwise the job's $tries/backoff and
        // failed() are dead code and a network blip gives up permanently.
        // A business refusal is a verdict, not a fault: it stays `false`.
        if ($response->failed()) {
            throw new \RuntimeException(
                'Klikpajak cancel failed '.$response->status().': '.$response->body()
            );
        }

        $json = $response->json();

        // "04" = approve/edit/cancel succeeded; anything else (e.g. "1106"
        // not found) means the cancel did not happen.
        return is_array($json) && in_array((string) ($json['code'] ?? ''), ['00', '04'], true);
    }

    public function testConnection(): bool
    {
        try {
            $url = $this->baseUrl.'/v1/subscription';

            $response = Http::withHeaders($this->headers('GET', $url))
                ->timeout(10)
                ->get($url);

            return ! $response->failed()
                && is_array($response->json())
                && (string) ($response->json('code') ?? '') === '00';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Poll GET /v1/efaktur/out/bulk/{request_id} until Mekari has processed
     * the batch and returns the per-invoice entries.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException when the batch fails or never finishes
     */
    private function awaitBulkEntries(string $requestId): array
    {
        $url = $this->baseUrl.'/v1/efaktur/out/bulk/'.rawurlencode($requestId);

        for ($attempt = 1; $attempt <= $this->bulkPollAttempts; $attempt++) {
            if ($attempt > 1 && $this->bulkPollIntervalMs > 0) {
                usleep($this->bulkPollIntervalMs * 1000);
            }

            $response = Http::withHeaders($this->headers('GET', $url))
                ->timeout(30)
                ->get($url);

            $json = $this->envelope($response, ['00', '03', '04', '06', '1801'], 'Batch submission failed');
            $code = (string) $json['code'];
            $entries = $json['data']['request'] ?? [];

            if ($code === '03') {
                continue; // still processing
            }

            if ($code === '1801') {
                $errors = collect(is_array($entries) ? $entries : [])
                    ->pluck('error_msg')
                    ->filter()
                    ->implode('; ');

                throw new \RuntimeException(
                    'Batch submission failed: '.($errors !== '' ? $errors : 'one or more invoices were rejected')
                );
            }

            if (is_array($entries) && $entries !== []) {
                return $entries;
            }
        }

        throw new \RuntimeException(
            'Batch submission still in progress after '.$this->bulkPollAttempts.' polls (request_id '.$requestId.')'
        );
    }

    /**
     * Live-API payload: Klikpajak Public API v1 "Create Sales Invoice".
     *
     * @return array<string, mixed>
     */
    private function buildPayload(FakturPajak $faktur): array
    {
        $customer = $faktur->sale?->customer;
        $discountTotal = 0.0;
        $priceTotal = 0.0;

        $items = $faktur->items->map(function ($item) use (&$discountTotal, &$priceTotal) {
            $unit = (float) $item->harga_satuan;
            $quantity = (float) $item->jumlah_barang;
            $discount = (float) $item->diskon;

            $discountTotal += $discount;
            $priceTotal += $unit * $quantity;

            return [
                'name' => $item->nama_barang,
                'unit_price' => $unit,
                'quantity' => $quantity,
                'selling_price' => $unit * $quantity,
                'discount' => $discount,
                'ppnbm_rate' => 0,
                'dpp' => (float) $item->dpp,
                'ppn' => (float) $item->ppn,
                'ppnbm' => 0,
            ];
        })->values()->all();

        return [
            'client_reference_id' => 'FP-'.$faktur->id,
            'transaction_detail' => '01',
            'additional_trx_detail' => '00',
            'substitution_flag' => (bool) $faktur->is_pengganti,
            'substituted_faktur_id' => null,
            // DJP assigns the real document_number: sending a pre-allocated
            // NSFP here is rejected ("supplied NSFP is unregistered").
            'document_number' => null,
            'document_date' => $faktur->tanggal_faktur->format('Y-m-d'),
            'reference' => $faktur->nsfp,
            'total_dpp' => (float) $faktur->dpp,
            'total_ppn' => (float) $faktur->ppn,
            'total_ppnbm' => (float) $faktur->ppnbm,
            'total_price' => round($priceTotal, 2),
            'total_discount' => round($discountTotal, 2),
            'downpayment_flag' => null,
            'downpayment_dpp' => 0,
            'downpayment_ppn' => 0,
            'downpayment_ppnbm' => 0,
            'customer' => [
                'name' => (string) ($faktur->nama_pembeli ?: ($customer?->name ?? '')),
                'npwp' => (string) preg_replace('/\D/', '', (string) ($faktur->npwp_pembeli ?: ($customer?->npwp ?? ''))),
                'address' => (string) ($faktur->alamat_pembeli ?: ($customer?->address ?? '')),
                'email' => $customer?->email,
            ],
            'items' => $items,
        ];
    }

    /**
     * Mekari HMAC request signing (developers.mekari.com/docs/kb/hmac-authentication):
     * signature = base64(HMAC-SHA256("date: {Date}\n{METHOD} {path?query} HTTP/1.1", clientSecret)).
     * The Date header and the signed date must be identical (300s clock
     * skew); Digest covers the exact body bytes on every non-GET call.
     *
     * @return array<string, string>
     */
    private function headers(string $method, string $url, ?string $body = null): array
    {
        $date = gmdate('D, d M Y H:i:s').' GMT';

        $parts = parse_url($url);
        $target = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        $payload = implode("\n", ['date: '.$date, strtoupper($method).' '.$target.' HTTP/1.1']);
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->clientSecret, true));

        $headers = [
            'Date' => $date,
            'Authorization' => sprintf(
                'hmac username="%s", algorithm="hmac-sha256", headers="date request-line", signature="%s"',
                $this->clientId,
                $signature
            ),
            'Accept' => 'application/json',
        ];

        // Spec: Digest is required on POST/PUT/PATCH/DELETE (empty body →
        // hash of ""); GET requests carry no body, so no Digest.
        if (strtoupper($method) !== 'GET') {
            $headers['Digest'] = 'SHA-256='.base64_encode(hash('sha256', (string) $body, true));
        }

        return $headers;
    }

    private function normalizeStatus(string $approvalStatus): string
    {
        return match ($approvalStatus) {
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            default => 'pending', // DRAFT, IN_PROGRESS, unknown, missing
        };
    }

    /**
     * Unwrap a {code, message, data} response and fail loudly on anything
     * that is not one of the API's accepted codes.
     *
     * @param  array<int, string>  $okCodes
     * @param  array<string, mixed>  $logContext
     * @return array<string, mixed>
     */
    private function envelope(Response $response, array $okCodes, string $failPrefix, array $logContext = []): array
    {
        $json = $response->failed() ? null : $response->json();
        $code = is_array($json) ? (string) ($json['code'] ?? '') : null;

        if ($response->failed() || $code === null || ! in_array($code, $okCodes, true)) {
            $detail = $response->failed()
                ? $response->status().': '.$response->body()
                : (($code !== null && $code !== '')
                    ? $code.': '.(($json['message'] ?? null) ?: $response->body())
                    : $response->body());

            Log::error('Klikpajak request failed', $logContext + [
                'context' => $failPrefix,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($failPrefix.' '.$detail);
        }

        return $json;
    }
}
