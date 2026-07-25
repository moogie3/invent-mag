<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FakturPajak;
use App\Models\FakturPajakItem;
use App\Models\NsfpPool;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EFakturService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    // -----------------------------------------------------------------------
    // Faktur Pajak Generation
    // -----------------------------------------------------------------------

    /**
     * Generate a FakturPajak from a Sales record.
     *
     * Guards:
     * - Tenant must be PKP (Pengusaha Kena Pajak)
     * - Sale must have ppn_enabled = true
     * - No existing FakturPajak for this sale (prevent duplicates)
     * - NSFP pool must have available numbers
     *
     * Atomicity:
     * - DB::transaction wraps NSFP allocation + FakturPajak + Items + Journal Entry
     * - lockForUpdate() on NSFP row prevents race conditions under concurrent requests
     */
    public function generateFakturPajak(Sales $sale): FakturPajak
    {
        $tenant = app('currentTenant');

        if (! $tenant->pkp_status) {
            throw new \DomainException('Tenant is not registered as PKP. Cannot issue Faktur Pajak. Please enable PKP status in e-Faktur Settings.');
        }

        if (! $sale->isPpn()) {
            throw new \DomainException('PPN is not enabled on this sale. Please select a PPN-enabled tax on the sales order.');
        }

        if ($sale->fakturPajak()->exists()) {
            throw new \DomainException('A Faktur Pajak has already been generated for this sale (Invoice: ' . $sale->invoice . ').');
        }

        return DB::transaction(function () use ($sale, $tenant) {
            // Allocate NSFP atomically — pessimistic lock prevents race conditions
            $nsfp = NsfpPool::available()
                ->lockForUpdate()
                ->first();

            if (! $nsfp) {
                throw new \DomainException('No available NSFP numbers. Please import more NSFP from e-Faktur Settings.');
            }

            $nsfp->update(['status' => 'used', 'used_at' => now()]);

            $customer = $sale->customer;
            $ppnRate  = $sale->ppn_rate ?? $tenant->ppn_rate ?? 11.00;

            // Calculate DPP (Dasar Pengenaan Pajak = taxable base, excl. PPN)
            // DPP = grand_total - total_tax (i.e., subtotal minus order discount, before any tax)
            $dpp = round($sale->total - $sale->total_tax, 2);
            $ppn = round($sale->total_tax, 2);

            $faktur = FakturPajak::create([
                'sales_id'        => $sale->id,
                'nsfp'            => $nsfp->nsfp,
                'tanggal_faktur'  => $sale->order_date->toDateString(),
                'masa_pajak'      => $sale->order_date->month,
                'tahun_pajak'     => $sale->order_date->year,
                'npwp_penjual'    => $tenant->npwp ?? '',
                'nama_penjual'    => $tenant->name,
                'alamat_penjual'  => Auth::user()->address ?? $tenant->name,
                'npwp_pembeli'    => $customer->npwp ?? '00.000.000.0-000.000',
                'nama_pembeli'    => $customer->name,
                'alamat_pembeli'  => $customer->alamat_npwp ?? $customer->address ?? '',
                'dpp'             => $dpp,
                'ppn'             => $ppn,
                'ppnbm'           => 0,
                'ppn_rate'        => $ppnRate,
                'status'          => 'draft',
            ]);

            // Update NSFP pool with back-reference to the generated faktur
            $nsfp->update(['faktur_pajak_id' => $faktur->id]);

            // Create line items from the sale's sales_items
            $sale->loadMissing('salesItems.product');

            foreach ($sale->salesItems as $item) {
                $itemDpp = round((float) $item->total, 2);
                $itemPpn = round($itemDpp * ($ppnRate / 100), 2);

                FakturPajakItem::create([
                    'faktur_pajak_id' => $faktur->id,
                    'nama_barang'     => $item->name ?? $item->product?->name ?? 'Produk',
                    'harga_satuan'    => $item->customer_price,
                    'jumlah_barang'   => $item->quantity,
                    'harga_total'     => $item->total,
                    'diskon'          => $item->discount ?? 0,
                    'dpp'             => $itemDpp,
                    'ppn'             => $itemPpn,
                    'kode_objek_pajak'=> '01',  // BKP default
                ]);
            }

            // Post PPN Keluaran journal entry via AccountingService
            $this->postPpnKeluaranJournalEntry($faktur, $sale);

            Log::info('FakturPajak generated', [
                'faktur_id' => $faktur->id,
                'nsfp'      => $faktur->nsfp,
                'sales_id'  => $sale->id,
                'tenant_id' => $tenant->id,
            ]);

            return $faktur->load('items');
        });
    }

    // -----------------------------------------------------------------------
    // Faktur Pajak Cancellation
    // -----------------------------------------------------------------------

    /**
     * Cancel a draft Faktur Pajak.
     * Returns the NSFP to the pool so it can be reused? No — DJP NSFP that
     * has been assigned cannot be returned to the pool; mark it as 'cancelled'.
     */
    public function cancelFakturPajak(FakturPajak $faktur): void
    {
        if (! in_array($faktur->status, ['draft'])) {
            throw new \DomainException('Only draft Faktur Pajak can be cancelled. Submitted/approved fakturs require a Faktur Pengganti.');
        }

        DB::transaction(function () use ($faktur) {
            // Reverse the PPN Keluaran journal entry if one was posted
            $ppnJournalEntry = \App\Models\JournalEntry::where('sourceable_type', FakturPajak::class)
                ->where('sourceable_id', $faktur->id)
                ->where('status', 'posted')
                ->first();

            if ($ppnJournalEntry) {
                $this->accountingService->reverseJournalEntry(
                    $ppnJournalEntry,
                    "Reversal — Faktur Pajak {$faktur->nsfp} cancelled"
                );
            }

            // Mark NSFP as cancelled (DJP numbers cannot be reused once allocated to a faktur)
            NsfpPool::where('nsfp', $faktur->nsfp)->update(['status' => 'cancelled']);

            $faktur->update(['status' => 'cancelled']);
        });
    }

    // -----------------------------------------------------------------------
    // Journal Entry for PPN Keluaran
    // -----------------------------------------------------------------------

    /**
     * Post PPN Keluaran (output VAT) journal entry.
     *
     * When a Faktur Pajak is issued:
     *   Dr. Accounts Receivable     ppn_amount   (customer owes us the PPN)
     *   Cr. PPN Keluaran (Liability) ppn_amount  (we owe DJP the PPN)
     *
     * Account IDs come from tenant's accounting_settings JSON — same pattern
     * as SalesService::createSale() and PosService.
     */
    protected function postPpnKeluaranJournalEntry(FakturPajak $faktur, Sales $sale): void
    {
        $tenant = app('currentTenant');
        $accountingSettings = $tenant->accounting_settings ?? [];

        $ppnKeluaranAccountId = $accountingSettings['ppn_keluaran_account_id'] ?? null;
        $arAccountId          = $accountingSettings['accounts_receivable_account_id'] ?? null;

        // If PPN accounts not yet configured, skip posting (don't crash — warn in log)
        if (! $ppnKeluaranAccountId || ! $arAccountId) {
            Log::warning('PPN journal entry skipped — ppn_keluaran_account_id or AR account not configured in accounting settings.', [
                'faktur_id' => $faktur->id,
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        $ppnKeluaranAccount = Account::find($ppnKeluaranAccountId);
        $arAccount          = Account::find($arAccountId);

        if (! $ppnKeluaranAccount || ! $arAccount) {
            Log::warning('PPN journal entry skipped — configured account not found.', [
                'faktur_id'               => $faktur->id,
                'ppn_keluaran_account_id' => $ppnKeluaranAccountId,
                'ar_account_id'           => $arAccountId,
            ]);
            return;
        }

        // Use AccountingService — never create journal entries directly
        $this->accountingService->createJournalEntry(
            description:    'PPN Keluaran — FP ' . $faktur->nsfp . ' / ' . $faktur->nama_pembeli,
            date:           Carbon::parse($faktur->tanggal_faktur),
            transactions:   [
                [
                    'account_id' => $arAccount->id,
                    'type'       => 'debit',
                    'amount'     => $faktur->ppn,
                ],
                [
                    'account_id' => $ppnKeluaranAccount->id,
                    'type'       => 'credit',
                    'amount'     => $faktur->ppn,
                ],
            ],
            sourceDocument: $faktur   // FakturPajak is the sourceable
        );
    }

    // -----------------------------------------------------------------------
    // PPN Calculation Utility
    // -----------------------------------------------------------------------

    /**
     * Calculate PPN components for a given subtotal.
     * Used in the sales form and AJAX endpoint to show real-time PPN preview.
     */
    public function calculatePpn(float $subtotal, ?float $ppnRate = null): array
    {
        $tenant = app('currentTenant');
        $rate   = $ppnRate ?? $tenant->ppn_rate ?? 11.0;
        $dpp    = round($subtotal, 2);
        $ppn    = round($dpp * ($rate / 100), 2);
        $total  = $dpp + $ppn;

        return compact('dpp', 'ppn', 'total', 'rate');
    }

    // -----------------------------------------------------------------------
    // NSFP Pool Management
    // -----------------------------------------------------------------------

    /**
     * Import a block of NSFP numbers from a newline-separated string.
     * Called from the e-Faktur settings page.
     *
     * Validates format: must match DJP NSFP pattern XXX.XXX-YY.XXXXXXXX
     * Skips duplicates silently (idempotent import).
     *
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function importNsfpPool(string $nsfpText): array
    {
        $lines    = preg_split('/\r?\n/', trim($nsfpText));
        $tenant   = app('currentTenant');
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($lines as $line) {
            $nsfp = trim($line);
            if (empty($nsfp)) continue;

            // Validate NSFP format: XXX.XXX-YY.XXXXXXXX
            if (! preg_match('/^\d{3}\.\d{3}-\d{2}\.\d{8}$/', $nsfp)) {
                $errors[] = "Invalid NSFP format: {$nsfp}";
                continue;
            }

            // Skip if already in pool (idempotent)
            if (NsfpPool::where('nsfp', $nsfp)->exists()) {
                $skipped++;
                continue;
            }

            NsfpPool::create([
                'tenant_id' => $tenant->id,
                'nsfp'      => $nsfp,
                'status'    => 'available',
            ]);
            $imported++;
        }

        return compact('imported', 'skipped', 'errors');
    }

    // -----------------------------------------------------------------------
    // DJP e-Faktur CSV Export
    // -----------------------------------------------------------------------

    /**
     * Export Faktur Pajak as DJP e-Faktur CSV format for a given tax period.
     *
     * This is the minimum viable e-Faktur compliance (same level as Zahir Online).
     * The CSV can be imported into DJP's e-Faktur desktop application.
     *
     * DJP CSV spec:
     * - FK row: Faktur Keluaran header
     * - OF row: item detail rows
     * - Amounts are in CENTS (integer, no decimal point) — multiply by 100, cast to int
     * - UTF-8 BOM required for Excel compatibility with Indonesian locale
     */
    public function exportCsvForDjp(int $masaPajak, int $tahunPajak): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $fakturs = FakturPajak::with(['items', 'sale'])
            ->where('masa_pajak', $masaPajak)
            ->where('tahun_pajak', $tahunPajak)
            ->whereIn('status', ['draft', 'approved'])
            ->orderBy('nsfp')
            ->get();

        $filename = sprintf('FK-%02d-%d-efaktur.csv', $masaPajak, $tahunPajak);

        return response()->streamDownload(function () use ($fakturs) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM — required for Indonesian Excel locale
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row per DJP spec
            fputcsv($handle, [
                'FK', 'KD_JENIS_TRANSAKSI', 'FG_PENGGANTI', 'NOMOR_FAKTUR',
                'MASA_PAJAK', 'TAHUN_PAJAK', 'TANGGAL_FAKTUR',
                'NPWP', 'NAMA', 'ALAMAT_LENGKAP',
                'JUMLAH_DPP', 'JUMLAH_PPN', 'JUMLAH_PPNBM',
                'ID_KETERANGAN_TAMBAHAN', 'FG_UANG_MUKA',
                'UANG_MUKA_DPP', 'UANG_MUKA_PPN', 'UANG_MUKA_PPNBM',
                'REFERENSI',
            ]);

            foreach ($fakturs as $faktur) {
                // FK row — Faktur header
                fputcsv($handle, [
                    'FAPR',
                    '01',   // 01 = penyerahan BKP/JKP dalam negeri biasa
                    $faktur->is_pengganti ? '1' : '0',
                    $faktur->nsfp,
                    $faktur->masa_pajak,
                    $faktur->tahun_pajak,
                    $faktur->tanggal_faktur->format('Y-m-d'),
                    $faktur->npwp_pembeli,
                    $faktur->nama_pembeli,
                    $faktur->alamat_pembeli,
                    // DJP format: amounts in CENTS (integer, multiply by 100, cast to int)
                    (int) round($faktur->dpp * 100),
                    (int) round($faktur->ppn * 100),
                    (int) round($faktur->ppnbm * 100),
                    '',     // keterangan tambahan — usually empty
                    '0',    // uang muka flag
                    '0', '0', '0',
                    $faktur->sale?->invoice ?? '',
                ]);

                // OF rows — item detail
                foreach ($faktur->items as $item) {
                    fputcsv($handle, [
                        'OF',
                        $item->kode_objek_pajak,  // 01 = BKP
                        $item->nama_barang,
                        (int) round($item->harga_satuan * 100),
                        $item->jumlah_barang,
                        (int) round($item->harga_total * 100),
                        (int) round($item->diskon * 100),
                        (int) round($item->dpp * 100),
                        (int) round($item->ppn * 100),
                        '0',  // PPnBM
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // -----------------------------------------------------------------------
    // SPT Masa PPN Summary
    // -----------------------------------------------------------------------

    /**
     * Get summary data for SPT Masa PPN monthly report.
     *
     * Returns totals for PPN Keluaran (output VAT) and faktur counts.
     * Note: PPN Masukan (input VAT from purchases) tracking is not in Phase 1 scope.
     */
    public function getSptMasaSummary(int $masaPajak, int $tahunPajak): array
    {
        $base = FakturPajak::where('masa_pajak', $masaPajak)
            ->where('tahun_pajak', $tahunPajak);

        $active = (clone $base)->whereIn('status', ['draft', 'submitted', 'approved']);

        return [
            'masa_pajak'   => $masaPajak,
            'tahun_pajak'  => $tahunPajak,
            'total_faktur' => (clone $base)->count(),
            'approved'     => (clone $base)->where('status', 'approved')->count(),
            'submitted'    => (clone $base)->where('status', 'submitted')->count(),
            'draft'        => (clone $base)->where('status', 'draft')->count(),
            'rejected'     => (clone $base)->where('status', 'rejected')->count(),
            'cancelled'    => (clone $base)->where('status', 'cancelled')->count(),
            'total_dpp'    => $active->sum('dpp'),
            'total_ppn'    => $active->sum('ppn'),
        ];
    }
}
