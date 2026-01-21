<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use App\Helpers\SalesReturnHelper;
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
        $filters = $request->only(['month', 'year']);
        $data = $this->salesReturnService->getSalesReturnIndexData($filters, $entries);
        return view('admin.sales-returns.index', $data);
    }

    public function create()
    {
        // Get sales that do NOT have any associated sales returns
        $sales = Sales::whereDoesntHave('salesReturns')->get();
        return view('admin.sales-returns.create', compact('sales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sales_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => 'required|json',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $this->salesService->createSalesReturn($request->all());

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return created successfully.');
    }

    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load(['sale.customer', 'items.product']); // Corrected relationship name

        $statusClass = SalesReturnHelper::getStatusClass($salesReturn->status);
        $statusText = SalesReturnHelper::getStatusText($salesReturn->status);

        return view('admin.sales-returns.show', compact('salesReturn', 'statusClass', 'statusText'));
    }

    public function edit(SalesReturn $salesReturn)
    {
        $sales = Sales::all();
        $isCompletedOrCanceled = in_array($salesReturn->status, ['Completed', 'Canceled']);
        return view('admin.sales-returns.edit', compact('salesReturn', 'sales', 'isCompletedOrCanceled'));
    }

    public function update(Request $request, SalesReturn $salesReturn)
    {
        $request->validate([
            'sales_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => 'required|json',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $this->salesReturnService->updateSalesReturn($salesReturn, $request->all());

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return updated successfully.');
    }

    public function destroy(SalesReturn $salesReturn)
    {
        $salesReturn->delete();
        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return deleted successfully.');
    }

    public function getSalesItems(Sales $sale)
    {
        return response()->json($sale->salesItems()->with('product')->get());
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        SalesReturn::whereIn('id', $ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected sales returns have been deleted.']);
    }

    public function bulkComplete(Request $request)
    {
        $ids = $request->input('ids');
        $this->salesReturnService->bulkCompleteSalesReturns($ids);
        return response()->json(['success' => true, 'message' => 'Selected sales returns have been marked as completed.']);
    }

    public function bulkCancel(Request $request)
    {
        $ids = $request->input('ids');
        $this->salesReturnService->bulkCancelSalesReturns($ids);
        return response()->json(['success' => true, 'message' => 'Selected sales returns have been marked as canceled.']);
    }

    public function modalView(SalesReturn $salesReturn)
    {
        $statusClass = SalesReturnHelper::getStatusClass($salesReturn->status);
        $statusText = SalesReturnHelper::getStatusText($salesReturn->status);

        return view('admin.layouts.modals.sales.srmodals-view', compact('salesReturn', 'statusClass', 'statusText'));
    }

    public function print($id)
    {
        try {
            return $this->salesReturnService->printReturn($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating return slip. ' . $e->getMessage());
        }
    }

    /**
     * @group Sales Returns
     * @summary Bulk Export Sales Returns
     * @bodyParam ids array required An array of sales return IDs to export. Example: [1, 2, 3]
     * @bodyParam export_option string required The export format ('pdf' or 'csv'). Example: "csv"
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