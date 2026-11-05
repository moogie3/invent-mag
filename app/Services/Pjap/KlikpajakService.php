<?php

namespace App\Services\Pjap;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mekari Klikpajak PJAP client — the concrete transport behind the
 * PjapInterface contract (Phase 2 of e-Faktur: direct DJP submission,
 * no manual CSV / desktop app).
 *
 * Every call carries X-Idempotency-Key so a retried job can never
 * double-submit a faktur to DJP (idempotency layer 2 of 5 — see
 * docs/ROADMAP.md §2.8).
 */
class KlikpajakService implements PjapInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $npwp;

    public function __construct(array $config)
    {
        $this->baseUrl   = $config['base_url'];
        $this->apiKey    = $config['api_key'];
        $this->apiSecret = $config['api_secret'];
        $this->npwp      = $config['npwp'];
    }

    public function submit(FakturPajak $faktur): string
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(30)
            // Idempotency key prevents duplicate submissions even if the request is retried
            ->withHeaders(['X-Idempotency-Key' => 'FP-'.$faktur->id.'-'.$faktur->nsfp])
            ->retry(3, sleepMilliseconds: 1000, throw: false)
            ->post($this->baseUrl.'/v1/faktur/upload', $this->buildPayload($faktur));

        if ($response->failed()) {
            Log::error('Klikpajak submit failed', [
                'faktur_id' => $faktur->id,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            throw new \RuntimeException('Klikpajak API error '.$response->status().': '.$response->body());
        }

        $submissionId = $response->json('data.submission_id');

        if (! is_string($submissionId) || $submissionId === '') {
            throw new \RuntimeException('Klikpajak submit response missing submission_id: '.$response->body());
        }

        return $submissionId;
    }

    public function submitBatch(array $fakturIds): array
    {
        $fakturs  = FakturPajak::with('items')->whereIn('id', $fakturIds)->get();
        $payloads = $fakturs->map(fn ($f) => $this->buildPayload($f))->values()->toArray();

        $response = Http::withHeaders($this->headers())
            ->timeout(120)
            ->post($this->baseUrl.'/v1/faktur/upload-batch', ['fakturs' => $payloads]);

        if ($response->failed()) {
            throw new \RuntimeException('Batch submission failed: '.$response->body());
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new \RuntimeException('Batch submission response malformed: '.$response->body());
        }

        return collect($data)
            ->keyBy('faktur_id')
            ->map(fn ($item) => $item['submission_id'] ?? null)
            ->toArray();
    }

    public function checkStatus(string $pjapSubmissionId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl.'/v1/faktur/status/'.$pjapSubmissionId);

        if ($response->failed()) {
            throw new \RuntimeException('Klikpajak status check failed: '.$response->body());
        }

        return [
            'status'           => $this->normalizeStatus($response->json('data.status')),
            'djp_reference'    => $response->json('data.nomor_faktur_djp'),
            'rejection_reason' => $response->json('data.keterangan'),
            'raw'              => $response->json(),
        ];
    }

    public function cancel(string $pjapSubmissionId): bool
    {
        $response = Http::withHeaders($this->headers())
            ->delete($this->baseUrl.'/v1/faktur/'.$pjapSubmissionId);

        return $response->successful();
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->baseUrl.'/v1/ping');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(FakturPajak $faktur): array
    {
        return [
            'kd_jenis_transaksi' => '01',
            'fg_pengganti'       => $faktur->is_pengganti ? '1' : '0',
            'nomor_faktur'       => $faktur->nsfp,
            'masa_pajak'         => $faktur->masa_pajak,
            'tahun_pajak'        => $faktur->tahun_pajak,
            'tanggal_faktur'     => $faktur->tanggal_faktur->format('Y-m-d'),
            'npwp_penjual'       => $faktur->npwp_penjual,
            'nama_penjual'       => $faktur->nama_penjual,
            'alamat_penjual'     => $faktur->alamat_penjual,
            'npwp_pembeli'       => $faktur->npwp_pembeli,
            'nama_pembeli'       => $faktur->nama_pembeli,
            'alamat_pembeli'     => $faktur->alamat_pembeli,
            'jumlah_dpp'         => $faktur->dpp,
            'jumlah_ppn'         => $faktur->ppn,
            'jumlah_ppnbm'       => $faktur->ppnbm,
            'detail'             => $faktur->items->map(fn ($item) => [
                'kode_objek_pajak' => $item->kode_objek_pajak,
                'nama_barang'      => $item->nama_barang,
                'harga_satuan'     => $item->harga_satuan,
                'jumlah_barang'    => $item->jumlah_barang,
                'diskon'           => $item->diskon,
                'dpp'              => $item->dpp,
                'ppn'              => $item->ppn,
            ])->toArray(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'X-API-Secret'  => $this->apiSecret,
        ];
    }

    private function normalizeStatus(?string $raw): string
    {
        return match (strtoupper((string) $raw)) {
            'APPROVED', 'DITERIMA', 'SUCCESS' => 'approved',
            'REJECTED', 'DITOLAK', 'FAILED'   => 'rejected',
            default                            => 'pending',
        };
    }
}
