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
use App\Models\Tax;
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
            'is_closed' => 'boolean',
        ]);

        $data = $request->all();
        $data['position'] = $pipeline->stages()->count();

        $stage = $pipeline->stages()->create($data);
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
        $opportunitiesQuery = SalesOpportunity::with(['customer', 'pipeline', 'stage']);

        if ($request->pipeline_id) {
            $opportunitiesQuery->where('sales_pipeline_id', $request->pipeline_id);
        }

        if ($request->stage_id) {
            $opportunitiesQuery->where('pipeline_stage_id', $request->stage_id);
        }

        $opportunities = $opportunitiesQuery->get();
        $totalPipelineValue = $opportunities->sum('amount');

        return response()->json([
            'opportunities' => $opportunities,
            'total_pipeline_value' => $totalPipelineValue,
        ]);
    }

    public function storeOpportunity(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $opportunity = SalesOpportunity::create([
                'customer_id' => $validatedData['customer_id'],
                'sales_pipeline_id' => $validatedData['sales_pipeline_id'],
                'pipeline_stage_id' => $validatedData['pipeline_stage_id'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'amount' => 0, // Will be calculated from items
                'expected_close_date' => $validatedData['expected_close_date'],
                'status' => $validatedData['status'],
            ]);

            $totalAmount = 0;
            foreach ($validatedData['items'] as $itemData) {
                $itemData['price'] = round($itemData['price']); // Round price to integer
                $opportunity->items()->create($itemData);
                $totalAmount += ($itemData['quantity'] * $itemData['price']);
            }

            $opportunity->update(['amount' => round($totalAmount)]); // Round total amount to integer

            DB::commit();

            return response()->json($opportunity->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create opportunity: ' . $e->getMessage(), 'type' => 'error'], 500);
        }
    }

    public function updateOpportunity(Request $request, SalesOpportunity $opportunity)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $opportunity->update([
                'customer_id' => $validatedData['customer_id'],
                'sales_pipeline_id' => $validatedData['sales_pipeline_id'],
                'pipeline_stage_id' => $validatedData['pipeline_stage_id'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'expected_close_date' => $validatedData['expected_close_date'],
                'status' => $validatedData['status'],
            ]);

            // Delete existing items and create new ones
            $opportunity->items()->delete();

            $totalAmount = 0;
            foreach ($validatedData['items'] as $itemData) {
                $itemData['price'] = round($itemData['price']); // Round price to integer
                $opportunity->items()->create($itemData);
                $totalAmount += ($itemData['quantity'] * $itemData['price']);
            }

            $opportunity->update(['amount' => round($totalAmount)]); // Round total amount to integer

            DB::commit();

            return response()->json($opportunity->load('items'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update opportunity: ' . $e->getMessage(), 'type' => 'error'], 500);
        }
    }

    public function destroyOpportunity(SalesOpportunity $opportunity)
    {
        $opportunity->delete();
        return response()->json(null, 204);
    }

    public function showOpportunity(SalesOpportunity $opportunity)
    {
        return response()->json($opportunity->load('customer', 'pipeline', 'stage', 'items.product'));
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
            return response()->json(['message' => 'Opportunity must be in a "won" status to be converted.', 'type' => 'error'], 400);
        }

        // Check if a sales order already exists for this opportunity
        if ($opportunity->sales) {
            return response()->json(['message' => 'This opportunity has already been converted to a sales order.', 'type' => 'error'], 400);
        }

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // 1. Get sales opportunity items
            $opportunityItems = $opportunity->items;

            if ($opportunityItems->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'No products associated with this opportunity.', 'type' => 'error'], 404);
            }

            // 2. Generate invoice number with PL- prefix
            $lastSalesInvoice = Sales::where('invoice', 'LIKE', 'PL-%')
                                    ->latest()
                                    ->first();

            $invoiceNumber = 1;
            if ($lastSalesInvoice) {
                $lastNumber = (int) substr($lastSalesInvoice->invoice, 3); // Get number after 'PL-'
                $invoiceNumber = $lastNumber + 1;
            }
            $invoice = 'PL-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

            // 3. Get tax information
            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;

            // 4. Calculate total amounts from opportunity items and round to integer
            $subTotal = round($opportunityItems->reduce(function ($carry, $item) {
                return $carry + ($item->quantity * $item->price);
            }, 0));
            $taxAmount = round($subTotal * ($taxRate / 100));
            $grandTotal = round($subTotal + $taxAmount);

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

            // 6. Create SalesItem records for each opportunity item and decrement product stock
            foreach ($opportunityItems as $item) {
                SalesItem::create([
                    'sales_id' => $salesOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'customer_price' => round($item->price), // Round price to integer
                    'total' => round($item->quantity * $item->price), // Round total to integer
                ]);

                // 7. Decrement product stock
                $product = Product::find($item->product_id);
                if ($product) {
                    if (isset($product->stock_quantity)) {
                        $product->decrement('stock_quantity', $item->quantity);
                    } elseif (isset($product->quantity)) {
                        $product->decrement('quantity', $item->quantity);
                    } elseif (isset($product->stock)) {
                        $product->decrement('stock', $item->quantity);
                    }
                }
            }

            // 8. Update the SalesOpportunity status and link to the new Sales record
            $opportunity->update([
                'status' => 'converted',
                'sales_id' => $salesOrder->id, // Link the opportunity to the sales order
            ]);

            DB::commit();

            return response()->json(['message' => 'Opportunity successfully converted to Sales Order.', 'type' => 'success', 'sales_id' => $salesOrder->id], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to convert opportunity: ' . $e->getMessage(), 'type' => 'error'], 500);
        }
    }
}