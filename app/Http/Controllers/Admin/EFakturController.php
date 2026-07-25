<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FakturPajak;
use App\Models\NsfpPool;
use App\Models\Sales;
use App\Services\EFakturService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;

class EFakturController extends Controller
{
    public function __construct(protected EFakturService $efakturService) {}

    // -----------------------------------------------------------------------
    // Index — list Faktur Pajak for a given tax period
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorize('view-efaktur');

        $masa  = (int) $request->get('masa', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $fakturs = FakturPajak::with(['sale.customer'])
            ->where('masa_pajak', $masa)
            ->where('tahun_pajak', $tahun)
            ->orderBy('nsfp')
            ->paginate(25)
            ->withQueryString();

        $summary   = $this->efakturService->getSptMasaSummary($masa, $tahun);
        $nsfpCount = NsfpPool::where('status', 'available')->count();

        return view('admin.efaktur.index', compact('fakturs', 'summary', 'masa', 'tahun', 'nsfpCount'));
    }

    // -----------------------------------------------------------------------
    // Show — single Faktur Pajak detail
    // -----------------------------------------------------------------------

    public function show(FakturPajak $fakturPajak)
    {
        $this->authorize('view-efaktur');

        $fakturPajak->load(['items', 'sale.customer', 'sale.salesItems.product']);
        return view('admin.efaktur.show', compact('fakturPajak'));
    }

    // -----------------------------------------------------------------------
    // Generate — create Faktur Pajak from a sale
    // -----------------------------------------------------------------------

    public function generate(Sales $sale)
    {
        $this->authorize('create-efaktur');

        try {
            $faktur = $this->efakturService->generateFakturPajak($sale);
            return redirect()->route('admin.efaktur.show', $faktur)
                ->with('success', 'Faktur Pajak ' . $faktur->nsfp . ' berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Cancel — cancel a draft Faktur Pajak
    // -----------------------------------------------------------------------

    public function cancel(FakturPajak $fakturPajak)
    {
        $this->authorize('create-efaktur');

        try {
            $this->efakturService->cancelFakturPajak($fakturPajak);
            return redirect()->route('admin.efaktur.index')
                ->with('success', 'Faktur Pajak ' . $fakturPajak->nsfp . ' dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Print — render Faktur Pajak as PDF via Dompdf
    // -----------------------------------------------------------------------

    public function print(FakturPajak $fakturPajak)
    {
        $this->authorize('view-efaktur');

        $fakturPajak->load(['items', 'sale.customer', 'tenant']);

        $html = view('admin.efaktur.faktur-pdf', compact('fakturPajak'))->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'FP-' . str_replace(['.', '-'], '', $fakturPajak->nsfp) . '.pdf';

        return $dompdf->stream($filename, ['Attachment' => false]);
    }

    // -----------------------------------------------------------------------
    // Export CSV — DJP e-Faktur CSV for a tax period
    // -----------------------------------------------------------------------

    public function exportCsv(Request $request)
    {
        $this->authorize('view-efaktur');

        $masa  = (int) $request->get('masa', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        return $this->efakturService->exportCsvForDjp($masa, $tahun);
    }

    // -----------------------------------------------------------------------
    // Settings — e-Faktur configuration (NPWP, PKP status, NSFP import)
    // -----------------------------------------------------------------------

    public function settings()
    {
        $this->authorize('manage-efaktur-settings');

        $tenant    = app('currentTenant');
        $nsfpCount = NsfpPool::where('status', 'available')->count();
        $nsfpUsed  = NsfpPool::where('status', 'used')->count();

        return view('admin.efaktur.settings', compact('tenant', 'nsfpCount', 'nsfpUsed'));
    }

    public function saveSettings(Request $request)
    {
        $this->authorize('manage-efaktur-settings');

        $validated = $request->validate([
            'tax_jurisdiction' => 'nullable|string|max:10',
            'npwp'       => 'nullable|string|max:20',
            'pkp_status' => 'boolean',
            'pkp_date'   => 'nullable|date',
            'ppn_rate'   => 'numeric|min:0|max:100',
        ]);

        // Ensure pkp_status is a boolean (checkbox sends '1' or omits key)
        $validated['pkp_status'] = $request->boolean('pkp_status');

        // Normalize empty string to null
        $validated['tax_jurisdiction'] = $validated['tax_jurisdiction'] ?: null;

        $tenant = app('currentTenant');
        $wasIndonesian = $tenant->tax_jurisdiction === 'ID';
        $willBeIndonesian = ($validated['tax_jurisdiction'] ?? null) === 'ID';

        // Safeguard: block disabling Indonesian jurisdiction if draft FakturPajak exist
        if ($wasIndonesian && ! $willBeIndonesian) {
            $draftCount = FakturPajak::where('status', 'draft')->count();
            if ($draftCount > 0) {
                return back()->withErrors([
                    'tax_jurisdiction' => __('messages.efaktur_cannot_disable_with_drafts', ['count' => $draftCount]),
                ])->withInput();
            }

            // Warn (but allow) if there are sales with PPN enabled but no FakturPajak
            $unissuedCount = Sales::where('ppn_enabled', true)
                ->whereDoesntHave('fakturPajak')
                ->count();
            if ($unissuedCount > 0) {
                return back()->withErrors([
                    'tax_jurisdiction' => __('messages.efaktur_cannot_disable_with_unissued', ['count' => $unissuedCount]),
                ])->withInput();
            }
        }

        $tenant->update($validated);

        return back()->with('success', __('messages.efaktur_settings_saved'));
    }

    public function importNsfp(Request $request)
    {
        $this->authorize('manage-efaktur-settings');

        $request->validate(['nsfp_list' => 'required|string']);

        $result = $this->efakturService->importNsfpPool($request->nsfp_list);

        $msg = "Import selesai: {$result['imported']} NSFP berhasil diimpor, {$result['skipped']} sudah ada.";

        if (count($result['errors']) > 0) {
            $msg .= ' Error: ' . implode('; ', array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $msg .= ' (dan ' . (count($result['errors']) - 5) . ' error lainnya)';
            }
        }

        return back()->with('success', $msg);
    }
}
