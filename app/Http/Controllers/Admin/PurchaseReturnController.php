<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PurchaseReturnHelper;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Rules\WholeReturnQuantities;
use App\Services\ActivityLogService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    protected $purchaseReturnService;

    protected $purchaseService;

    public function __construct(PurchaseReturnService $purchaseReturnService, PurchaseService $purchaseService)
    {
        $this->purchaseReturnService = $purchaseReturnService;
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $filters = $request->only(['month', 'year', 'search']);
        $data = $this->purchaseReturnService->getPurchaseReturnIndexData($filters, $entries);

        return view('admin.por.index', $data);
    }

    public function create()
    {
        $purchases = Purchase::whereHas('items')->get();

        return view('admin.por.create', compact('purchases'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:po,id',
            'return_date' => 'required|date',
            'items' => ['required', 'json', new WholeReturnQuantities],
            'total_amount' => 'required|numeric',
            // The index only ever counts Pending/Completed/Canceled, so any
            // other string written here would sit in the table and vanish
            // from every status tile. The allowed list lives on the model.
            'status' => ['required', 'string', 'in:'.implode(',', PurchaseReturn::$statuses)],
        ]);

        // A cap refusal is a normal business answer: it comes back as the
        // flash error the session partial renders, not as a 500.
        try {
            $this->purchaseReturnService->createPurchaseReturn($request->all());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.por.index')->with('success', 'Purchase return created successfully.');
    }

    public function show(PurchaseReturn $por)
    {
        $por->load(['purchase.supplier', 'items.product']);

        $statusClass = PurchaseReturnHelper::getStatusClass($por->status);
        $statusText = PurchaseReturnHelper::getStatusText($por->status);

        // What this return reverses in the ledger, derived by the same code
        // path the journal uses, so the screen cannot disagree with the GL.
        $taxReversal = $this->purchaseReturnService->deriveReversal(
            $por->purchase,
            (float) $por->total_amount,
        );

        return view('admin.por.show', compact('por', 'statusClass', 'statusText', 'taxReversal'));
    }

    public function edit(PurchaseReturn $por)
    {
        $purchases = Purchase::all();
        $isCompletedOrCanceled = in_array($por->status, ['Completed', 'Canceled']);

        return view('admin.por.edit', compact('por', 'purchases', 'isCompletedOrCanceled'));
    }

    public function update(Request $request, PurchaseReturn $por)
    {
        $request->validate([
            'purchase_id' => 'required|exists:po,id',
            'return_date' => 'required|date',
            'items' => ['required', 'json', new WholeReturnQuantities],
            'total_amount' => 'required|numeric',
            // The index only ever counts Pending/Completed/Canceled, so any
            // other string written here would sit in the table and vanish
            // from every status tile. The allowed list lives on the model.
            'status' => ['required', 'string', 'in:'.implode(',', PurchaseReturn::$statuses)],
        ]);

        // Same contract as store(): the refusal is a flash error, not a 500.
        try {
            $this->purchaseReturnService->updatePurchaseReturn($por, $request->all());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.por.index')->with('success', 'Purchase return updated successfully.');
    }

    public function destroy(PurchaseReturn $por)
    {
        $this->purchaseReturnService->deletePurchaseReturn($por);

        return redirect()->route('admin.por.index')->with('success', 'Purchase return deleted successfully.');
    }

    /**
     * The proportional tax reversal a return against this purchase will
     * book, as JSON for the create/edit form's live preview. It calls
     * deriveReversal(), the same code path the journal and the show page
     * use, so the preview cannot disagree with what gets posted.
     */
    public function deriveReversal(Request $request)
    {
        $purchase = Purchase::whereKey($request->integer('purchase_id'))
            ->where('tenant_id', app('currentTenant')->id)
            ->first();

        abort_unless($purchase, 404);

        $total = max(0, (float) $request->query('total', 0));

        return response()->json($this->purchaseReturnService->deriveReversal($purchase, $total));
    }

    public function getPurchaseItems(Purchase $purchase)
    {
        $purchase->load(['items.product', 'purchaseReturns.items']);

        $items = $purchase->items->map(function ($item) use ($purchase) {
            // Every LIVE return holds those units already; only a Canceled one
            // has given them back. Counting just Completed returns (the old
            // rule) offered Pending returns' quantities again on the form.
            $returnedQuantity = $purchase->purchaseReturns
                ->where('status', '!=', 'Canceled')
                ->flatMap(fn ($pr) => $pr->items ?? collect())
                ->where('product_id', $item->product_id)
                ->sum('quantity');

            $item->available_quantity = max(0, $item->quantity - $returnedQuantity);

            return $item;
        });

        return response()->json($items);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        $this->purchaseReturnService->bulkDeletePurchaseReturns($ids);

        return response()->json(['success' => true, 'message' => 'Selected purchase returns have been deleted.']);
    }

    public function bulkComplete(Request $request)
    {
        $ids = $request->input('ids');
        $counts = $this->purchaseReturnService->bulkCompletePurchaseReturns($ids);

        // Log only the rows that actually transitioned: a canceled row that
        // was skipped never became Completed, and logging it would say so.
        $completedReturns = PurchaseReturn::whereIn('id', $counts['completed_ids'])->get();
        foreach ($completedReturns as $pr) {
            app(ActivityLogService::class)->logUpdate(
                ActivityLog::MODULE_PURCHASE_RETURN,
                "Bulk marked purchase return #{$pr->id} as Completed",
                $pr
            );
        }

        return response()->json([
            'success' => true,
            'message' => $this->bulkCompleteMessage($counts, 'purchase return'),
            'counts' => $counts,
        ]);
    }

    /**
     * Turn the service's per-row counts into one sentence for the result
     * toast. Canceled rows are reported as skipped, never as failures: the
     * batch succeeded for every row it could complete.
     *
     * @param  array{completed: int, already_completed: int, skipped: int}  $counts
     */
    private function bulkCompleteMessage(array $counts, string $noun): string
    {
        $parts = [];

        if ($counts['completed'] > 0) {
            $parts[] = $counts['completed'].' '.$noun.($counts['completed'] > 1 ? 's' : '').' marked as completed';
        }
        if ($counts['already_completed'] > 0) {
            $parts[] = $counts['already_completed'].' '.$noun.($counts['already_completed'] > 1 ? 's' : '').' already completed';
        }
        if ($counts['skipped'] > 0) {
            $parts[] = $counts['skipped'].' '.$noun.($counts['skipped'] > 1 ? 's' : '').' skipped: canceled returns cannot be completed';
        }

        if ($parts === []) {
            return 'No '.$noun.'s were selected.';
        }

        return implode('. ', $parts).'.';
    }

    public function bulkCancel(Request $request)
    {
        $ids = $request->input('ids');
        $purchaseReturns = PurchaseReturn::whereIn('id', $ids)->get();
        foreach ($purchaseReturns as $pr) {
            $this->purchaseReturnService->cancelPurchaseReturn($pr);
            app(ActivityLogService::class)->logUpdate(
                ActivityLog::MODULE_PURCHASE_RETURN,
                "Bulk canceled purchase return #{$pr->id}",
                $pr
            );
        }

        return response()->json(['success' => true, 'message' => 'Selected purchase returns have been marked as canceled.']);
    }

    public function modalView(PurchaseReturn $por)
    {
        $por->load(['purchase.supplier', 'items.product']); // Eager load relationships

        $statusClass = PurchaseReturnHelper::getStatusClass($por->status);
        $statusText = PurchaseReturnHelper::getStatusText($por->status);

        return view('admin.layouts.modals.po.pormodals-view', compact('por', 'statusClass', 'statusText'));
    }

    public function print($id)
    {
        try {
            return $this->purchaseReturnService->printReturn($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating return slip. '.$e->getMessage());
        }
    }

    /**
     * @group Purchase Returns
     *
     * @summary Bulk Export Purchase Returns
     *
     * @bodyParam ids array required An array of purchase return IDs to export. Example: [1, 2, 3]
     * @bodyParam export_option string required The export format ('pdf' or 'csv'). Example: "csv"
     *
     * @response 200 "The exported file."
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $file = $this->purchaseReturnService->bulkExportPurchaseReturns($request->all(), $request->ids, $request->export_option);

            return $file;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting purchase returns. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
