<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CancelFakturPajakJob;
use App\Jobs\SubmitFakturPajakBatchJob;
use App\Jobs\SubmitFakturPajakJob;
use App\Models\FakturPajak;
use App\Models\NsfpPool;
use App\Models\Sales;
use App\Services\EFakturService;
use App\Services\TaxService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EFakturController extends Controller
{
    public function __construct(protected EFakturService $efakturService) {}

    // -----------------------------------------------------------------------
    // Index — list Faktur Pajak for a given tax period
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorize('view-efaktur');

        $masa = (int) $request->get('masa', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $fakturs = FakturPajak::with(['sale.customer'])
            ->where('masa_pajak', $masa)
            ->where('tahun_pajak', $tahun)
            ->orderBy('nsfp')
            ->paginate(25)
            ->withQueryString();

        $summary = $this->efakturService->getSptMasaSummary($masa, $tahun);
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
                ->with('success', 'Faktur Pajak '.$faktur->nsfp.' berhasil dibuat.');
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
                ->with('success', 'Faktur Pajak '.$fakturPajak->nsfp.' dibatalkan.');
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

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'FP-'.str_replace(['.', '-'], '', $fakturPajak->nsfp).'.pdf';

        return $dompdf->stream($filename, ['Attachment' => false]);
    }

    // -----------------------------------------------------------------------
    // Export CSV — DJP e-Faktur CSV for a tax period
    // -----------------------------------------------------------------------

    public function exportCsv(Request $request)
    {
        $this->authorize('view-efaktur');

        $masa = (int) $request->get('masa', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        return $this->efakturService->exportCsvForDjp($masa, $tahun);
    }

    // -----------------------------------------------------------------------
    // Settings — e-Faktur configuration (NPWP, PKP status, NSFP import)
    // -----------------------------------------------------------------------

    public function settings()
    {
        $this->authorize('manage-efaktur-settings');

        $tenant = app('currentTenant');
        $nsfpCount = NsfpPool::where('status', 'available')->count();
        $nsfpUsed = NsfpPool::where('status', 'used')->count();

        return view('admin.efaktur.settings', compact('tenant', 'nsfpCount', 'nsfpUsed'));
    }

    public function saveSettings(Request $request)
    {
        $this->authorize('manage-efaktur-settings');

        $validated = $request->validate([
            'tax_jurisdiction' => 'nullable|string|max:10',
            'npwp' => 'nullable|string|max:20',
            'pkp_status' => 'boolean',
            'pkp_date' => 'nullable|date',
            'ppn_rate' => 'numeric|min:0|max:100',
            // Phase 2 — Klikpajak PJAP credentials (posted from their own card)
            'klikpajak_api_key' => 'nullable|string|max:255',
            'klikpajak_secret' => 'nullable|string|max:255',
            'klikpajak_use_sandbox' => 'nullable|boolean',
        ]);

        // Clear stored credentials when the fields are posted empty
        if (array_key_exists('klikpajak_api_key', $validated)) {
            $validated['klikpajak_api_key'] = $validated['klikpajak_api_key'] ?: null;
        }
        if (array_key_exists('klikpajak_secret', $validated)) {
            $validated['klikpajak_secret'] = $validated['klikpajak_secret'] ?: null;
        }
        if (array_key_exists('klikpajak_use_sandbox', $validated)) {
            // The form carries a hidden "0" so unchecking the box still posts
            $validated['klikpajak_use_sandbox'] = $request->boolean('klikpajak_use_sandbox');
        }

        $tenant = app('currentTenant');

        // Jurisdiction/PKP fields come from the main settings form, the
        // Klikpajak credentials from their own card — so only run the
        // jurisdiction logic (and the PKP checkbox fallback) when the main
        // form was the one submitted, or a Klikpajak save would silently
        // turn Indonesian jurisdiction off.
        if ($request->has('tax_jurisdiction')) {
            // Ensure pkp_status is a boolean (checkbox sends '1' or omits key)
            $validated['pkp_status'] = $request->boolean('pkp_status');

            // Normalize empty string to null
            $validated['tax_jurisdiction'] = $validated['tax_jurisdiction'] ?: null;

            $wasIndonesian = $tenant->tax_jurisdiction === 'ID';
            $willBeIndonesian = $validated['tax_jurisdiction'] === 'ID';

            // Safeguard: block disabling Indonesian jurisdiction if draft FakturPajak exist
            if ($wasIndonesian && ! $willBeIndonesian) {
                $check = $tenant->canDisableIndonesianJurisdiction();
                if (! $check['safe']) {
                    return back()->withErrors([
                        'tax_jurisdiction' => $check['message'],
                    ])->withInput();
                }
            }
        }

        $tenant->update($validated);

        if (($validated['tax_jurisdiction'] ?? null) === 'ID') {
            $created = app(TaxService::class)->ensureIndonesianDefaults($tenant);
            if (! empty($created)) {
                Log::info('Auto-created default Indonesian tax records', [
                    'tenant_id' => $tenant->id,
                    'taxes' => $created,
                ]);
            }
        }

        return back()->with('success', __('messages.efaktur_settings_saved'));
    }

    // -----------------------------------------------------------------------
    // Submit ke DJP (Phase 2) — queue a PJAP submission for a draft faktur
    // -----------------------------------------------------------------------

    public function submitToDjp(FakturPajak $fakturPajak)
    {
        $this->authorize('create-efaktur');

        if (! $fakturPajak->isSubmittable()) {
            return back()->with('error', __('messages.efaktur_not_submittable'));
        }

        $tenant = app('currentTenant');
        if (! $tenant->klikpajak_api_key) {
            return back()->with('error', __('messages.efaktur_klikpajak_not_configured'));
        }

        // Queued: DJP/PJAP calls take seconds each; the job owns the
        // idempotency guards (WithoutOverlapping + pjap_submission_id check).
        SubmitFakturPajakJob::dispatch($fakturPajak)->onQueue('tax-submissions');

        return back()->with('success', __('messages.efaktur_queued_for_djp'));
    }

    // -----------------------------------------------------------------------
    // Bulk submit ke DJP (Phase 2): one PJAP call for many draft fakturs
    // -----------------------------------------------------------------------

    public function submitBatch(Request $request)
    {
        $this->authorize('create-efaktur');

        // Deliberately not a hard 'required' rule: the index page renders
        // session() flashes only, so a validation error would be silent.
        // Empty selection is handled below where it can actually be seen.
        $request->validate([
            'faktur_ids'   => ['nullable', 'array'],
            'faktur_ids.*' => ['integer'],
        ]);

        $selected = array_values((array) $request->input('faktur_ids', []));

        if ($selected === []) {
            return back()->with('error', __('messages.efaktur_batch_none_selected'));
        }

        $tenant = app('currentTenant');
        if (! $tenant->klikpajak_api_key) {
            return back()->with('error', __('messages.efaktur_klikpajak_not_configured'));
        }

        // Restrict to what is actually submittable. The tenant scope on
        // FakturPajak means ids from another tenant never match, so a forged
        // list collapses to an empty selection instead of crossing tenants.
        $ids = FakturPajak::whereIn('id', $selected)
            ->where('status', 'draft')
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return back()->with('error', __('messages.efaktur_not_submittable'));
        }

        $skipped = count($selected) - count($ids);

        // Queued: the bulk transport polls DJP for up to ~2 minutes.
        SubmitFakturPajakBatchJob::dispatch($ids, $tenant->id)->onQueue('tax-submissions');

        $message = __('messages.efaktur_batch_queued', ['count' => count($ids)]);

        if ($skipped > 0) {
            $message .= ' '.__('messages.efaktur_batch_skipped', ['count' => $skipped]);
        }

        return back()->with('success', $message);
    }

    // -----------------------------------------------------------------------
    // Cancel at DJP (Phase 2): void a faktur DJP already accepted
    // -----------------------------------------------------------------------

    public function cancelAtDjp(FakturPajak $fakturPajak)
    {
        $this->authorize('create-efaktur');

        // Without a PJAP id there is nothing to void on DJP's side; the
        // Phase 1 local cancel (cancelFakturPajak) covers drafts instead.
        if (! $fakturPajak->pjap_submission_id) {
            return back()->with('error', __('messages.efaktur_not_submitted_to_djp'));
        }

        if ($fakturPajak->status === 'cancelled') {
            return back()->with('error', __('messages.efaktur_already_cancelled'));
        }

        $tenant = app('currentTenant');
        if (! $tenant->klikpajak_api_key) {
            return back()->with('error', __('messages.efaktur_klikpajak_not_configured'));
        }

        // Queued: DJP/PJAP calls take seconds; the job owns the idempotency
        // guard and only writes local state after DJP confirms the void.
        CancelFakturPajakJob::dispatch($fakturPajak)->onQueue('tax-submissions');

        return back()->with('success', __('messages.efaktur_cancel_queued_for_djp'));
    }

    public function importNsfp(Request $request)
    {
        $this->authorize('manage-efaktur-settings');

        $request->validate(['nsfp_list' => 'required|string']);

        $result = $this->efakturService->importNsfpPool($request->nsfp_list);

        $msg = "Import selesai: {$result['imported']} NSFP berhasil diimpor, {$result['skipped']} sudah ada.";

        if (count($result['errors']) > 0) {
            $msg .= ' Error: '.implode('; ', array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $msg .= ' (dan '.(count($result['errors']) - 5).' error lainnya)';
            }
        }

        return back()->with('success', $msg);
    }
}
