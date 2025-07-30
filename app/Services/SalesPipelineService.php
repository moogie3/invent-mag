<?php

namespace App\Services;

use App\Models\SalesPipeline;
use App\Models\PipelineStage;
use App\Models\SalesOpportunity;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Customer;

class SalesPipelineService
{
    public function getSalesPipelineIndexData()
    {
        $pipelines = SalesPipeline::with('stages')->get();
        $customers = Customer::all();

        return compact('pipelines', 'customers');
    }

    public function createPipeline(array $data): SalesPipeline
    {
        return SalesPipeline::create($data);
    }

    public function updatePipeline(SalesPipeline $pipeline, array $data): SalesPipeline
    {
        $pipeline->update($data);
        return $pipeline;
    }

    public function deletePipeline(SalesPipeline $pipeline): void
    {
        $pipeline->delete();
    }

    public function createStage(SalesPipeline $pipeline, array $data): PipelineStage
    {
        $data['position'] = $pipeline->stages()->count();
        return $pipeline->stages()->create($data);
    }

    public function updateStage(PipelineStage $stage, array $data): PipelineStage
    {
        $stage->update($data);
        return $stage;
    }

    public function deleteStage(PipelineStage $stage): void
    {
        $stage->delete();
    }

    public function reorderStages(SalesPipeline $pipeline, array $stages): void
    {
        foreach ($stages as $stageData) {
            PipelineStage::where('id', $stageData['id'])
                ->where('sales_pipeline_id', $pipeline->id)
                ->update(['position' => $stageData['position']]);
        }
    }

    public function createOpportunity(array $data): SalesOpportunity
    {
        return DB::transaction(function () use ($data) {
            $opportunity = SalesOpportunity::create([
                'customer_id' => $data['customer_id'],
                'sales_pipeline_id' => $data['sales_pipeline_id'],
                'pipeline_stage_id' => $data['pipeline_stage_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'amount' => 0, // Will be calculated from items
                'expected_close_date' => $data['expected_close_date'],
                'status' => $data['status'],
            ]);

            $totalAmount = 0;
            foreach ($data['items'] as $itemData) {
                $itemData['price'] = round($itemData['price'], 2);
                $opportunity->items()->create($itemData);
                $totalAmount += ($itemData['quantity'] * $itemData['price']);
            }

            $opportunity->update(['amount' => round($totalAmount, 2)]);

            return $opportunity;
        });
    }

    public function updateOpportunity(SalesOpportunity $opportunity, array $data): SalesOpportunity
    {
        return DB::transaction(function () use ($opportunity, $data) {
            $updateData = [
                'customer_id' => $data['customer_id'],
                'sales_pipeline_id' => $data['sales_pipeline_id'],
                'pipeline_stage_id' => $data['pipeline_stage_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'expected_close_date' => $data['expected_close_date'],
                'status' => $data['status'],
            ];

            $opportunity->items()->delete();
            $totalAmount = 0;
            $itemsToCreate = [];
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $price = round($itemData['price'], 2);
                    $quantity = $itemData['quantity'];
                    $itemsToCreate[] = [
                        'product_id' => $itemData['product_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                    ];
                    $totalAmount += ($quantity * $price);
                }
            }

            $updateData['amount'] = round($totalAmount, 2);

            $opportunity->update($updateData);

            if (!empty($itemsToCreate)) {
                $opportunity->items()->createMany($itemsToCreate);
            }

            return $opportunity->load('items');
        });
    }

    public function deleteOpportunity(SalesOpportunity $opportunity): void
    {
        $opportunity->delete();
    }

    public function moveOpportunity(SalesOpportunity $opportunity, int $stageId): SalesOpportunity
    {
        $opportunity->update(['pipeline_stage_id' => $stageId]);
        return $opportunity;
    }

    public function convertToSalesOrder(SalesOpportunity $opportunity): Sales
    {
        if ($opportunity->status !== 'won') {
            throw new \Exception('Opportunity must be in a "won" status to be converted.');
        }

        if ($opportunity->sales) {
            throw new \Exception('This opportunity has already been converted to a sales order.');
        }

        return DB::transaction(function () use ($opportunity) {
            $opportunityItems = $opportunity->items;

            if ($opportunityItems->isEmpty()) {
                throw new \Exception('No products associated with this opportunity.');
            }

            $lastSalesInvoice = Sales::where('invoice', 'LIKE', 'PL-%')
                ->latest()
                ->first();

            $invoiceNumber = 1;
            if ($lastSalesInvoice) {
                $lastNumber = (int) substr($lastSalesInvoice->invoice, 3);
                $invoiceNumber = $lastNumber + 1;
            }
            $invoice = 'PL-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;

            $subTotal = round($opportunityItems->reduce(fn($carry, $item) => $carry + ($item->quantity * $item->price), 0));
            $taxAmount = round($subTotal * ($taxRate / 100));
            $grandTotal = round($subTotal + $taxAmount);

            $salesOrder = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $opportunity->customer_id,
                'user_id' => Auth::id(),
                'order_date' => now(),
                'due_date' => $opportunity->expected_close_date ?? now()->addDays(30),
                'payment_type' => '-',
                'order_discount' => 0,
                'order_discount_type' => 'fixed',
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'status' => 'Unpaid',
                'is_pos' => false,
                'sales_opportunity_id' => $opportunity->id,
            ]);

            foreach ($opportunityItems as $item) {
                SalesItem::create([
                    'sales_id' => $salesOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'customer_price' => round($item->price),
                    'total' => round($item->quantity * $item->price),
                ]);

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

            $opportunity->update([
                'status' => 'converted',
                'sales_id' => $salesOrder->id,
            ]);

            return $salesOrder;
        });
    }
}
