<?php

namespace App\Services;

use App\Helpers\SalesHelper;
use App\Models\Customer;
use App\Models\PipelineStage;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sales;
use App\Models\SalesOpportunity;
use App\Models\SalesPipeline;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        if (isset($data['is_default']) && $data['is_default']) {
            SalesPipeline::where('is_default', true)->update(['is_default' => false]);
        }

        return SalesPipeline::create($data);
    }

    public function updatePipeline(SalesPipeline $pipeline, array $data): SalesPipeline
    {
        if (isset($data['is_default']) && $data['is_default']) {
            SalesPipeline::where('id', '!=', $pipeline->id)->update(['is_default' => false]);
        }
        $pipeline->update($data);

        return $pipeline;
    }

    public function deletePipeline(SalesPipeline $pipeline): void
    {
        $pipeline->delete();
    }

    public function createStage(SalesPipeline $pipeline, array $data): PipelineStage
    {
        // Get the maximum existing position for stages within this pipeline,
        // or -1 if no stages exist, then add 1 to get the next position.
        $data['position'] = ($pipeline->stages()->max('position') ?? -1) + 1;

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
        DB::transaction(function () use ($pipeline, $stages) {
            // Step 1: Temporarily set positions to a non-conflicting value
            // We'll use a large number to avoid conflicts with existing positions
            $tempPositionOffset = 1000000; // A sufficiently large offset

            foreach ($stages as $stageData) {
                PipelineStage::where('id', $stageData['id'])
                    ->where('sales_pipeline_id', $pipeline->id)
                    ->update(['position' => $stageData['id'] + $tempPositionOffset]);
            }

            // Step 2: Update to final positions
            foreach ($stages as $stageData) {
                PipelineStage::where('id', $stageData['id'])
                    ->where('sales_pipeline_id', $pipeline->id)
                    ->update(['position' => $stageData['position']]);
            }
        });
    }

    public function createOpportunity(array $data): SalesOpportunity
    {
        \Log::debug('createOpportunity: Starting with data', $data);

        return DB::transaction(function () use ($data) {
            \Log::debug('createOpportunity: Inside transaction');
            // exists: is raw SQL with no tenant filter, so every id on this
            // request would accept another tenant's row. Re-resolve each one
            // through its scoped model: a cross-tenant id reads back as null
            // and is refused before anything is written.
            $customer = Customer::findOrFail($data['customer_id']);
            $pipeline = SalesPipeline::findOrFail($data['sales_pipeline_id']);
            $stage = PipelineStage::findOrFail($data['pipeline_stage_id']);
            foreach ($data['items'] as $itemData) {
                Product::findOrFail($itemData['product_id']);
            }

            $opportunity = SalesOpportunity::create([
                'customer_id' => $customer->id,
                'sales_pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'amount' => 0,
                'expected_close_date' => $data['expected_close_date'],
                'status' => $data['status'],
            ]);
            \Log::debug('createOpportunity: Opportunity created', ['opportunity_id' => $opportunity->id]);

            $totalAmount = 0;
            foreach ($data['items'] as $itemData) {
                \Log::debug('createOpportunity: Processing item', $itemData);
                $itemData['price'] = round($itemData['price'], 2);
                $opportunity->items()->create($itemData);
                $totalAmount += ($itemData['quantity'] * $itemData['price']);
            }
            \Log::debug('createOpportunity: Items processed, totalAmount', ['totalAmount' => $totalAmount]);

            $defaultTax = Tax::effectiveDefault();
            $defaultTaxRate = $defaultTax ? (float) $defaultTax->rate : 0;
            $defaultTaxType = $defaultTax ? $defaultTax->type : 'none';
            $defaultTaxAmount = SalesHelper::calculateTaxAmount($totalAmount, $defaultTaxRate, $defaultTaxType);
            $grandTotal = $defaultTaxType === 'inclusive' ? $totalAmount : $totalAmount + $defaultTaxAmount;

            $opportunity->update(['amount' => round($grandTotal, 2)]);
            \Log::debug('createOpportunity: Opportunity amount updated', ['amount' => $opportunity->amount]);

            return $opportunity;
        });
    }

    public function updateOpportunity(SalesOpportunity $opportunity, array $data): SalesOpportunity
    {
        \Log::debug('updateOpportunity: Starting with opportunity and data', ['opportunity_id' => $opportunity->id, 'data' => $data]);

        return DB::transaction(function () use ($opportunity, $data) {
            \Log::debug('updateOpportunity: Inside transaction');
            // Same scoped re-resolution as createOpportunity(): exists: is
            // raw SQL and would repoint an existing opportunity at another
            // tenant's rows.
            $customer = Customer::findOrFail($data['customer_id']);
            $pipeline = SalesPipeline::findOrFail($data['sales_pipeline_id']);
            $stage = PipelineStage::findOrFail($data['pipeline_stage_id']);
            foreach ($data['items'] as $itemData) {
                Product::findOrFail($itemData['product_id']);
            }

            $updateData = [
                'customer_id' => $customer->id,
                'sales_pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'expected_close_date' => $data['expected_close_date'],
                'status' => $data['status'],
            ];
            \Log::debug('updateOpportunity: Update data prepared', $updateData);

            $opportunity->items()->delete();
            \Log::debug('updateOpportunity: Existing items deleted');

            $totalAmount = 0;
            $itemsToCreate = [];
            if (! empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    \Log::debug('updateOpportunity: Processing item', $itemData);
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
            \Log::debug('updateOpportunity: Items processed, totalAmount', ['totalAmount' => $totalAmount]);

            $defaultTax = Tax::effectiveDefault();
            $defaultTaxRate = $defaultTax ? (float) $defaultTax->rate : 0;
            $defaultTaxType = $defaultTax ? $defaultTax->type : 'none';
            $defaultTaxAmount = SalesHelper::calculateTaxAmount($totalAmount, $defaultTaxRate, $defaultTaxType);
            $grandTotal = $defaultTaxType === 'inclusive' ? $totalAmount : $totalAmount + $defaultTaxAmount;

            $updateData['amount'] = round($grandTotal, 2);
            \Log::debug('updateOpportunity: Opportunity amount updated in updateData', ['amount' => $updateData['amount']]);

            $opportunity->update($updateData);
            \Log::debug('updateOpportunity: Opportunity updated');

            if (! empty($itemsToCreate)) {
                $opportunity->items()->createMany($itemsToCreate);
                \Log::debug('updateOpportunity: New items created');
            }

            return $opportunity->load('items');
        });
    }

    public function deleteOpportunity(SalesOpportunity $opportunity): void
    {
        DB::transaction(function () use ($opportunity) {
            // When deleting an opportunity, we DO NOT delete the linked Sales Order.
            // The Sales Order is a financial record and must persist even if the opportunity is removed.
            // The database foreign key is set to ON DELETE SET NULL, so the link will be cleared automatically.

            $opportunity->delete();
        });
    }

    public function moveOpportunity(SalesOpportunity $opportunity, int $stageId): SalesOpportunity
    {
        $opportunity->update(['pipeline_stage_id' => $stageId]);

        return $opportunity;
    }

    public function printOpportunity(int $id)
    {
        $opportunity = SalesOpportunity::with(['customer', 'items.product', 'pipeline', 'stage'])->findOrFail($id);
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        $html = view('admin.sales.opportunity-print-pdf', compact('opportunity', 'shopname', 'address'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('quotation-'.$opportunity->id.'.pdf', ['Attachment' => false]);
    }

    public function generateQuotation(int $opportunityId): Quotation
    {
        $opportunity = SalesOpportunity::with('items.product', 'customer')->findOrFail($opportunityId);

        $quotationNumber = $this->generateQuotationNumber($opportunity);

        $subtotal = 0;
        $items = [];

        foreach ($opportunity->items as $item) {
            $lineTotal = $item->quantity * $item->price;
            $subtotal += $lineTotal;
            $items[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'discount' => 0,
                'discount_type' => 'percentage',
                'tax_type' => 'none',
                'tax_rate' => null,
                'tax_amount' => 0,
                'subtotal' => round($lineTotal, 2),
                'total' => round($lineTotal, 2),
            ];
        }

        return DB::transaction(function () use ($opportunity, $quotationNumber, $subtotal, $items) {
            $tax = Tax::effectiveDefault();
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';
            $taxAmount = SalesHelper::calculateTaxAmount($subtotal, $taxRate, $taxType);
            $grandTotal = $taxType === 'inclusive' ? $subtotal : $subtotal + $taxAmount;

            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'customer_id' => $opportunity->customer_id,
                'user_id' => Auth::id(),
                'quotation_date' => now(),
                'valid_until' => now()->addDays(14),
                'status' => 'draft',
                'subtotal' => round($subtotal, 2),
                'tax_type' => $taxType,
                // NOT NULL column: null (no tax configured) crashed the insert.
                'tax_rate' => $taxRate,
                'tax_amount' => round($taxAmount, 2),
                'discount_type' => 'none',
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_id' => $tax?->id,
                'total' => round($grandTotal, 2),
                'sales_opportunity_id' => $opportunity->id,
                'notes' => $opportunity->notes,
                'terms_conditions' => $opportunity->terms_conditions,
                // Quotation has no items() relation, only this JSON cast:
                // the old items()->create loop threw BadMethodCallException.
                'items' => $items,
            ]);

            return $quotation;
        });
    }

    /**
     * Per-day QTN sequence for this tenant, skipping any number already on
     * file. A count()+1 proposal reuses the number a delete left behind and
     * hits the per-tenant unique key.
     */
    private function generateQuotationNumber(SalesOpportunity $opportunity): string
    {
        $prefix = 'QTN-'.now()->format('Ymd');
        $scoped = fn () => Quotation::where('tenant_id', $opportunity->tenant_id);

        $last = $scoped()
            ->where('quotation_number', 'like', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        $newNum = 1;
        if ($last) {
            // Everything after the last dash: QTN-20261115-10000 reads as
            // 10000, not 0000, so the sequence never wraps back to 0001.
            $newNum = (int) substr($last->quotation_number, strrpos($last->quotation_number, '-') + 1) + 1;
        }

        do {
            $quotationNumber = "{$prefix}-".str_pad($newNum, 4, '0', STR_PAD_LEFT);
            $newNum++;
        } while ($scoped()->where('quotation_number', $quotationNumber)->exists());

        return $quotationNumber;
    }

    public function convertToSalesOrder(SalesOpportunity $opportunity): Sales
    {
        if ($opportunity->status === 'converted' || $opportunity->sales_id) {
            throw new \Exception('This opportunity has already been converted to a sales order.');
        }

        if ($opportunity->status !== 'won') {
            throw new \Exception('Opportunity must be in a "won" status to be converted.');
        }

        return DB::transaction(function () use ($opportunity) {
            $opportunityItems = $opportunity->items;

            if ($opportunityItems->isEmpty()) {
                throw new \Exception('No products associated with this opportunity.');
            }

            // Same INV sequence every other writer uses: the local PL- block
            // filed pipeline sales outside the invoice numbering, so the
            // tenant's next manual invoice did not follow from it.
            $invoice = app(SalesService::class)->generateInvoiceNumber();

            $tax = Tax::effectiveDefault();
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';

            $subTotal = round($opportunityItems->reduce(fn ($carry, $item) => $carry + ($item->quantity * $item->price), 0));
            $taxAmount = SalesHelper::calculateTaxAmount($subTotal, $taxRate, $taxType);

            if ($taxType === 'inclusive') {
                $grandTotal = $subTotal;
            } else {
                $grandTotal = $subTotal + $taxAmount;
            }

            // Assign to Main Warehouse by default for pipeline conversions
            $mainWarehouse = Warehouse::where('is_main', true)->first();
            if (! $mainWarehouse) {
                // Fallback to first available if no main
                $mainWarehouse = Warehouse::first();
                if (! $mainWarehouse) {
                    throw new \Exception('No warehouse available to fulfill this order.');
                }
            }

            // PPN snapshot — same gate as createSale(). PPh stays off: the
            // pipeline has no withholding-tax selection (identical to
            // createSale() when no pph_tax_id is supplied), but the columns
            // are written explicitly like every other sales writer.
            $ppnApplied = app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false);

            $salesOrder = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $opportunity->customer_id,
                'user_id' => Auth::id(),
                'warehouse_id' => $mainWarehouse->id,
                'order_date' => now(),
                'due_date' => $opportunity->expected_close_date ?? now()->addDays(30),
                'payment_type' => '-',
                'order_discount' => 0,
                'order_discount_type' => 'fixed',
                'tax_id' => $tax?->id,
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'tax_type' => $taxType,
                'total' => $grandTotal,
                'status' => 'Unpaid',
                'type' => Sales::TYPE_REGULAR,
                'sales_opportunity_id' => $opportunity->id,
                'customer_credit_used' => 0,
                'notes' => $opportunity->notes,
                'terms_conditions' => $opportunity->terms_conditions,
                'ppn_enabled' => $ppnApplied,
                'ppn_rate' => $ppnApplied ? $taxRate : null,
                'ppn_amount' => $ppnApplied ? $taxAmount : 0,
                'pph_enabled' => false,
                'pph_rate' => null,
                'pph_amount' => 0,
                'pph_tax_id' => null,
            ]);

            // Line items, stock movement, COGS and the sale journal in one
            // step — previously this path decremented physical stock but
            // posted no journal at all, so GL inventory drifted from
            // physical inventory and revenue never reached the ledger.
            $lineItems = $opportunityItems->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => 0,
                'discount_type' => 'fixed',
            ])->values()->all();

            app(SalesService::class)->finalizeConvertedSale($salesOrder, $lineItems);

            $opportunity->update([
                'status' => 'converted',
                'sales_id' => $salesOrder->id,
            ]);

            return $salesOrder;
        });
    }
}
