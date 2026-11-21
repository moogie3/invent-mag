<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SalesReturnHelper;
use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\SalesReturn;
use App\Rules\WholeReturnQuantities;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    protected $salesReturnService;

    protected $salesService;

    public function __construct(SalesReturnService $salesReturnService, SalesService $salesService)
    {
        $this->salesReturnService = $salesReturnService;
        $this->salesService = $salesService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $filters = $request->only(['month', 'year', 'search']);
        $data = $this->salesReturnService->getSalesReturnIndexData($filters, $entries);

        return view('admin.sales-returns.index', $data);
    }

    public function create()
    {
        $sales = Sales::whereHas('salesItems')->get();

        return view('admin.sales-returns.create', compact('sales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sales_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => ['required', 'json', new WholeReturnQuantities],
            'total_amount' => 'required|numeric',
            // The index only ever counts Pending/Completed/Canceled, so any
            // other string written here would sit in the table and vanish
            // from every status tile. The allowed list lives on the model.
            'status' => ['required', 'string', 'in:'.implode(',', SalesReturn::$statuses)],
        ]);

        // A refusal (a product not on the invoice, an empty selection) is a
        // normal business answer: it comes back as the flash error the
        // session partial renders, with the form's input kept, not as a 500.
        try {
            $this->salesReturnService->createSalesReturn($request->all());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return created successfully.');
    }

    public function update(Request $request, SalesReturn $salesReturn)
    {
        $request->validate([
            'sales_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => ['required', 'json', new WholeReturnQuantities],
            'total_amount' => 'required|numeric',
            // The index only ever counts Pending/Completed/Canceled, so any
            // other string written here would sit in the table and vanish
            // from every status tile. The allowed list lives on the model.
            'status' => ['required', 'string', 'in:'.implode(',', SalesReturn::$statuses)],
        ]);

        // Same contract as store(): the refusal is a flash error, not a 500.
        try {
            $this->salesReturnService->updateSalesReturn($salesReturn, $request->all());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return updated successfully.');
    }

    public function destroy(SalesReturn $salesReturn)
    {
        try {
            $this->salesReturnService->deleteSalesReturn($salesReturn);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.sales-returns.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return deleted successfully.');
    }

    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load(['sale.customer', 'items.product', 'credit']); // Corrected relationship name

        $statusClass = SalesReturnHelper::getStatusClass($salesReturn->status);
        $statusText = SalesReturnHelper::getStatusText($salesReturn->status);

        // What this return reverses in the ledger, derived by the same code
        // path the journal uses, so the screen cannot disagree with the GL.
        $taxReversal = $this->salesReturnService->deriveReversal(
            $salesReturn->sale,
            (float) $salesReturn->total_amount,
        );

        return view('admin.sales-returns.show', compact('salesReturn', 'statusClass', 'statusText', 'taxReversal'));
    }

    public function edit(SalesReturn $salesReturn)
    {
        $salesReturn->load('items.salesItem');
        $sales = Sales::whereHas('salesItems')->get();
        $isCompletedOrCanceled = in_array($salesReturn->status, ['Completed', 'Canceled']);

        return view('admin.sales-returns.edit', compact('salesReturn', 'sales', 'isCompletedOrCanceled'));
    }

    /**
     * The proportional tax reversal a return against this sale will book,
     * as JSON for the create/edit form's live preview. It calls
     * deriveReversal(), the same code path the journal and the show page
     * use, so the preview cannot disagree with what gets posted.
     */
    public function deriveReversal(Request $request)
    {
        $sale = Sales::whereKey($request->integer('sale_id'))
            ->where('tenant_id', app('currentTenant')->id)
            ->first();

        abort_unless($sale, 404);

        $total = max(0, (float) $request->query('total', 0));

        return response()->json($this->salesReturnService->deriveReversal($sale, $total));
    }

    public function getSalesItems(Sales $sale, Request $request)
    {
        $sale->load(['salesItems.product', 'salesReturns.items.salesItem']);

        // Optional: when editing an existing return, exclude its items from the
        // "already returned" count so the user sees the correct available qty.
        $excludeReturnId = $request->query('exclude_return_id');

        // Build a map of sales_item_id -> total returned qty from COMPLETED returns
        // (excluding current return being edited, if any).
        $completedReturns = $sale->salesReturns->where('status', 'Completed');
        if ($excludeReturnId) {
            $completedReturns = $completedReturns->where('id', '!=', (int) $excludeReturnId);
        }

        // Group return items by their sales_item_id to get returned qty per line item
        $returnedBySalesItem = [];
        foreach ($completedReturns as $sr) {
            foreach ($sr->items as $item) {
                if ($item->sales_item_id) {
                    if (! isset($returnedBySalesItem[$item->sales_item_id])) {
                        $returnedBySalesItem[$item->sales_item_id] = 0;
                    }
                    $returnedBySalesItem[$item->sales_item_id] += $item->quantity;
                }
            }
        }

        // Now compute available_quantity for each sales item
        $items = $sale->salesItems->map(function ($item) use ($returnedBySalesItem) {
            $returnedQty = $returnedBySalesItem[$item->id] ?? 0;
            $item->available_quantity = max(0, $item->quantity - $returnedQty);

            return $item;
        });

        return response()->json($items);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        $counts = $this->salesReturnService->bulkDeleteSalesReturns($ids);

        $message = $counts['deleted'].' sales return'.($counts['deleted'] === 1 ? '' : 's').' deleted.';
        if ($counts['skipped'] > 0) {
            $message .= ' '.$counts['skipped'].' skipped: returns already refunded as cash cannot be deleted.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'counts' => $counts,
        ]);
    }

    public function bulkComplete(Request $request)
    {
        $ids = $request->input('ids');
        $counts = $this->salesReturnService->bulkCompleteSalesReturns($ids);

        return response()->json([
            'success' => true,
            'message' => $this->bulkCompleteMessage($counts, 'sales return'),
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
        $this->salesReturnService->bulkCancelSalesReturns($ids);

        return response()->json(['success' => true, 'message' => 'Selected sales returns have been marked as canceled.']);
    }

    /**
     * Pay a return's remaining customer credit back as cash.
     *
     * Full balance only: the caller cannot choose an amount, so there is no
     * partial-refund arithmetic to disagree with the ledger.
     */
    public function refundCredit(SalesReturn $salesReturn)
    {
        try {
            $this->salesReturnService->refundCustomerCredit($salesReturn);

            return redirect()
                ->route('admin.sales-returns.show', $salesReturn->id)
                ->with('success', __('messages.credit_refund_success'));
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.sales-returns.show', $salesReturn->id)
                ->with('error', $e->getMessage());
        }
    }

    public function modalView(SalesReturn $salesReturn)
    {
        $salesReturn->load('sale');
        $statusClass = SalesReturnHelper::getStatusClass($salesReturn->status);
        $statusText = SalesReturnHelper::getStatusText($salesReturn->status);

        return view('admin.layouts.modals.sales.srmodals-view', compact('salesReturn', 'statusClass', 'statusText'));
    }

    public function print($id)
    {
        try {
            return $this->salesReturnService->printReturn($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating return slip. '.$e->getMessage());
        }
    }

    /**
     * @group Sales Returns
     *
     * @summary Bulk Export Sales Returns
     *
     * @bodyParam ids array required An array of sales return IDs to export. Example: [1, 2, 3]
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
            $file = $this->salesReturnService->bulkExportSalesReturns($request->all(), $request->ids, $request->export_option);

            return $file;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting sales returns. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
