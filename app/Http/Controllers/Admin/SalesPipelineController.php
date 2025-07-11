<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\SalesPipeline;
use App\Models\PipelineStage;
use App\Models\SalesOpportunity;
use Illuminate\Validation\Rule;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SalesPipelineController extends Controller
{
    public function index()
    {
        $pipelines = SalesPipeline::with('stages')->get();
        $customers = Customer::all();

        return view('admin.sales.pipeline', compact('pipelines', 'customers'));
    }

    // Sales Pipeline Management
    public function indexPipelines()
    {
        $pipelines = SalesPipeline::with('stages')->get();
        return response()->json($pipelines);
    }

    public function storePipeline(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_pipelines',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $pipeline = SalesPipeline::create($request->all());
        return response()->json($pipeline, 201);
    }

    public function updatePipeline(Request $request, SalesPipeline $pipeline)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sales_pipelines')->ignore($pipeline->id)],
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $pipeline->update($request->all());
        return response()->json($pipeline);
    }

    public function destroyPipeline(SalesPipeline $pipeline)
    {
        $pipeline->delete();
        return response()->json(null, 204);
    }

    // Pipeline Stage Management
    public function storeStage(Request $request, SalesPipeline $pipeline)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pipeline_stages')->where(function ($query) use ($pipeline) {
                return $query->where('sales_pipeline_id', $pipeline->id);
            })],
            'position' => 'required|integer|min:0',
            'is_closed' => 'boolean',
        ]);

        $stage = $pipeline->stages()->create($request->all());
        return response()->json($stage, 201);
    }

    public function updateStage(Request $request, PipelineStage $stage)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pipeline_stages')->ignore($stage->id)->where(function ($query) use ($stage) {
                return $query->where('sales_pipeline_id', $stage->sales_pipeline_id);
            })],
            'position' => 'required|integer|min:0',
            'is_closed' => 'boolean',
        ]);

        $stage->update($request->all());
        return response()->json($stage);
    }

    public function destroyStage(PipelineStage $stage)
    {
        $stage->delete();
        return response()->json(null, 204);
    }

    public function reorderStages(Request $request, SalesPipeline $pipeline)
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.position' => 'required|integer|min:0',
        ]);

        foreach ($request->stages as $stageData) {
            PipelineStage::where('id', $stageData['id'])
                ->where('sales_pipeline_id', $pipeline->id)
                ->update(['position' => $stageData['position']]);
        }

        return response()->json(['message' => 'Stages reordered successfully']);
    }

    // Sales Opportunity Management
    public function indexOpportunities(Request $request)
    {
        $opportunities = SalesOpportunity::with(['customer', 'pipeline', 'stage'])
            ->when($request->pipeline_id, function ($query) use ($request) {
                $query->where('sales_pipeline_id', $request->pipeline_id);
            })
            ->when($request->stage_id, function ($query) use ($request) {
                $query->where('pipeline_stage_id', $request->stage_id);
            })
            ->get();

        return response()->json($opportunities);
    }

    public function storeOpportunity(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
        ]);

        $opportunity = SalesOpportunity::create($request->all());
        return response()->json($opportunity, 201);
    }

    public function updateOpportunity(Request $request, SalesOpportunity $opportunity)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
        ]);

        $opportunity->update($request->all());
        return response()->json($opportunity);
    }

    public function destroyOpportunity(SalesOpportunity $opportunity)
    {
        $opportunity->delete();
        return response()->json(null, 204);
    }

    public function showOpportunity(SalesOpportunity $opportunity)
    {
        return response()->json($opportunity->load('customer', 'pipeline', 'stage'));
    }

    public function moveOpportunity(Request $request, SalesOpportunity $opportunity)
    {
        $request->validate([
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        $opportunity->update(['pipeline_stage_id' => $request->pipeline_stage_id]);
        return response()->json($opportunity);
    }

    public function convertToSalesOrder(SalesOpportunity $opportunity)
    {
        // Validate that the opportunity is in a 'won' status
        if ($opportunity->status !== 'won') {
            return response()->json(['message' => 'Opportunity must be in a "won" status to be converted.'], 400);
        }

        // Check if a sales order already exists for this opportunity
        if ($opportunity->sales) {
            return response()->json(['message' => 'This opportunity has already been converted to a sales order.'], 400);
        }

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // 1. Get a product to associate with the sales item (for now, just the first one)
            $product = Product::first();
            if (!$product) {
                DB::rollBack();
                return response()->json(['message' => 'No products available to create a sales order item.'], 404);
            }

            // 2. Generate invoice number
            $lastInvoice = Sales::latest()->first();
            $invoiceNumber = $lastInvoice ? intval(substr($lastInvoice->invoice, -4)) + 1 : 1;
            $invoice = 'INV-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

            // 3. Get tax information
            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;

            // 4. Calculate total amounts (assuming opportunity amount is the subtotal for simplicity)
            $subTotal = $opportunity->amount ?? 0;
            $taxAmount = $subTotal * ($taxRate / 100);
            $grandTotal = $subTotal + $taxAmount;

            // 5. Create the Sales record
            $salesOrder = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $opportunity->customer_id,
                'user_id' => Auth::id(), // Assign current authenticated user
                'order_date' => now(),
                'due_date' => $opportunity->expected_close_date ?? now()->addDays(30), // Default due date
                'payment_type' => '-', // Default payment type
                'order_discount' => 0,
                'order_discount_type' => 'fixed',
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'status' => 'Unpaid', // New sales orders are typically unpaid initially
                'is_pos' => false,
                'sales_opportunity_id' => $opportunity->id,
            ]);

            // 6. Create a SalesItem record for the product
            SalesItem::create([
                'sales_id' => $salesOrder->id,
                'product_id' => $product->id,
                'quantity' => 1, // Assuming 1 unit for the opportunity amount
                'discount' => 0,
                'discount_type' => 'fixed',
                'customer_price' => $subTotal, // Price is the total opportunity amount
                'total' => $subTotal, // Total for this item is the subtotal
            ]);

            // 7. Decrement product stock
            if (isset($product->stock_quantity)) {
                $product->decrement('stock_quantity', 1);
            } elseif (isset($product->quantity)) {
                $product->decrement('quantity', 1);
            } elseif (isset($product->stock)) {
                $product->decrement('stock', 1);
            }

            // 8. Update the SalesOpportunity status and link to the new Sales record
            $opportunity->update([
                'status' => 'converted',
                'sales_id' => $salesOrder->id, // Link the opportunity to the sales order
            ]);

            DB::commit();

            return response()->json(['message' => 'Opportunity successfully converted to Sales Order.', 'sales_id' => $salesOrder->id], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to convert opportunity: ' . $e->getMessage()], 500);
        }
    }
}