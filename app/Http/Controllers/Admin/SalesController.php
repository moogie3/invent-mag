<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use App\Models\User;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $filters = $request->only(['month', 'year']);
        $data = $this->salesService->getSalesIndexData($filters, $entries);

        return view('admin.sales.index', $data);
    }

    public function create()
    {
        $data = $this->salesService->getSalesCreateData();

        return view('admin.sales.sales-create', $data);
    }

    public function edit($id)
    {
        $data = $this->salesService->getSalesEditData($id);

        return view('admin.sales.sales-edit', $data);
    }

    public function view($id)
    {
        $data = $this->salesService->getSalesViewData($id);

        return view('admin.sales.sales-view', $data);
    }

    public function modalViews($id)
    {
        try {
            $sales = $this->salesService->getSalesForModal($id);
            return view('admin.layouts.modals.salesmodals-view', compact('sales'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Sales record not found for modal view: {$id}");
            return response('<div class="alert alert-danger">Sales record not found.</div>', 404);
        } catch (\Exception $e) {
            Log::error("Error loading sales modal view for ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response('<div class="alert alert-danger">Error loading sales details: ' . $e->getMessage() . '</div>', 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice' => 'nullable|string|unique:sales,invoice',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|json',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
        ]);

        try {
            $this->salesService->createSale($request->all());
            return redirect()->route('admin.sales')->with('success', 'Sale created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'products' => 'required|json',
        ]);

        $sale = Sales::findOrFail($id);

        try {
            $this->salesService->updateSale($sale, $request->all());
            return redirect()->route('admin.sales.view', $id)->with('success', 'Sale updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function getCustomerPrice(Customer $customer, Product $product)
    {
        $pastPrice = $this->salesService->getPastCustomerPriceForProduct($customer, $product);
        return response()->json(['past_price' => $pastPrice]);
    }

    public function getSalesMetrics()
    {
        $metrics = $this->salesService->getSalesMetrics();

        return response()->json($metrics);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:sales,id',
        ]);

        try {
            $this->salesService->bulkDeleteSales($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted sales order(s)",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting sales orders. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sales,id',
        ]);

        try {
            $updatedCount = $this->salesService->bulkMarkPaid($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} sales order(s) as paid.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating sales orders.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $sale = Sales::findOrFail($id);

        try {
            $this->salesService->deleteSale($sale);
            return redirect()->route('admin.sales')->with('success', 'Sales order deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.sales')->with('error', 'Error deleting sales order. Please try again.');
        }
    }
}
