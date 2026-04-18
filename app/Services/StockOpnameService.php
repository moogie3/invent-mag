<?php

namespace App\Services;

use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\ProductWarehouse;
use App\Models\StockAdjustment;
use App\Models\Product;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class StockOpnameService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function createOpname(int $warehouseId, string $scheduledDate, ?string $notes)
    {
        return DB::transaction(function () use ($warehouseId, $scheduledDate, $notes) {
            $user = Auth::user();
            
            $opname = StockOpname::create([
                'tenant_id' => $user->tenant_id,
                'reference_number' => $this->generateReferenceNumber(),
                'warehouse_id' => $warehouseId,
                'status' => 'Draft',
                'scheduled_date' => $scheduledDate,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            // Take snapshot of current stock for the selected warehouse
            $products = ProductWarehouse::where('warehouse_id', $warehouseId)
                ->where('tenant_id', $user->tenant_id)
                ->get();

            $items = [];
            foreach ($products as $pw) {
                $items[] = [
                    'stock_opname_id' => $opname->id,
                    'product_id' => $pw->product_id,
                    'expected_qty' => $pw->quantity,
                    'counted_qty' => null,
                    'variance' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Also include products that might not have a ProductWarehouse record yet but exist
            $productIdsWithStock = $products->pluck('product_id')->toArray();
            $productsWithoutStock = Product::where('tenant_id', $user->tenant_id)
                ->whereNotIn('id', $productIdsWithStock)
                ->get();
                
            foreach ($productsWithoutStock as $product) {
                $items[] = [
                    'stock_opname_id' => $opname->id,
                    'product_id' => $product->id,
                    'expected_qty' => 0,
                    'counted_qty' => null,
                    'variance' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Chunk insert for performance
            foreach (array_chunk($items, 500) as $chunk) {
                StockOpnameItem::insert($chunk);
            }

            return $opname;
        });
    }

    public function updateCounts(StockOpname $opname, array $counts)
    {
        return DB::transaction(function () use ($opname, $counts) {
            foreach ($counts as $productId => $countedQty) {
                if ($countedQty === null || $countedQty === '') continue;
                
                $item = StockOpnameItem::where('stock_opname_id', $opname->id)
                    ->where('product_id', $productId)
                    ->first();
                    
                if ($item) {
                    $item->update([
                        'counted_qty' => (float) $countedQty,
                        'variance' => (float) $countedQty - $item->expected_qty
                    ]);
                }
            }
            
            if ($opname->status === 'Draft') {
                $opname->update(['status' => 'In Progress']);
            }
            
            return $opname;
        });
    }

    public function completeOpname(StockOpname $opname)
    {
        if ($opname->status === 'Completed') {
            throw new Exception('Stock Opname is already completed.');
        }

        return DB::transaction(function () use ($opname) {
            $user = Auth::user();
            $tenant = $user->tenant;
            
            // Get all items with a variance
            $itemsWithVariance = $opname->items()->whereNotNull('variance')->where('variance', '!=', 0)->get();
            
            $totalShrinkageValue = 0;
            $totalExcessValue = 0;

            foreach ($itemsWithVariance as $item) {
                $product = $item->product;
                
                // 1. Update ProductWarehouse
                $pw = ProductWarehouse::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $opname->warehouse_id,
                        'tenant_id' => $opname->tenant_id,
                    ],
                    ['quantity' => 0]
                );
                
                $pw->quantity = $item->counted_qty;
                $pw->save();

                // 2. Create StockAdjustment
                StockAdjustment::create([
                    'tenant_id' => $opname->tenant_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $opname->warehouse_id,
                    'adjustment_type' => $item->variance > 0 ? 'increase' : 'decrease',
                    'quantity_before' => $item->expected_qty,
                    'quantity_after' => $item->counted_qty,
                    'adjustment_amount' => abs($item->variance),
                    'reason' => "Stock Opname: {$opname->reference_number}",
                    'adjusted_by' => $user->id,
                ]);

                // Calculate value for accounting
                $value = abs($item->variance) * $product->price; // price is the cost/buying price
                if ($item->variance > 0) {
                    $totalExcessValue += $value;
                } else {
                    $totalShrinkageValue += $value;
                }
            }

            // 3. Accounting Integration
            if ($tenant->hasFeature('accounting') && ($totalShrinkageValue > 0 || $totalExcessValue > 0)) {
                $this->processAccounting($opname, $totalShrinkageValue, $totalExcessValue);
            }

            $opname->update([
                'status' => 'Completed',
                'completed_at' => now(),
            ]);

            return $opname;
        });
    }
    
    protected function processAccounting(StockOpname $opname, float $totalShrinkageValue, float $totalExcessValue)
    {
        $accountingSettings = Auth::user()->accounting_settings;
        if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['cost_of_goods_sold_account_id'])) {
            // Silently skip if accounting not configured
            return;
        }

        $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);
        // We use COGS or a dedicated Shrinkage account. Here we use COGS as fallback since it's an expense.
        $expenseAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id']); 

        if (!$inventoryAccount || !$expenseAccount) {
            return;
        }

        $transactions = [];

        // If there is shrinkage (loss of inventory), we Credit Inventory and Debit Expense
        if ($totalShrinkageValue > 0) {
            $transactions[] = ['account_name' => $expenseAccount->name, 'type' => 'debit', 'amount' => $totalShrinkageValue];
            $transactions[] = ['account_name' => $inventoryAccount->name, 'type' => 'credit', 'amount' => $totalShrinkageValue];
        }

        // If there is excess (gain of inventory), we Debit Inventory and Credit Expense (or a revenue account, but expense reduction works)
        if ($totalExcessValue > 0) {
            $transactions[] = ['account_name' => $inventoryAccount->name, 'type' => 'debit', 'amount' => $totalExcessValue];
            $transactions[] = ['account_name' => $expenseAccount->name, 'type' => 'credit', 'amount' => $totalExcessValue];
        }
        
        if (!empty($transactions)) {
            $this->accountingService->createJournalEntry(
                "Stock Opname Variance #{$opname->reference_number}",
                Carbon::parse($opname->completed_at ?? now()),
                $transactions,
                $opname // sourceable
            );
        }
    }

    protected function generateReferenceNumber(): string
    {
        $tenantId = Auth::user()->tenant_id;
        $prefix = 'SO-';
        $date = date('ymd');
        
        $latest = StockOpname::where('tenant_id', $tenantId)
            ->where('reference_number', 'like', "{$prefix}{$date}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $sequence = (int) substr($latest->reference_number, -4);
            $newSequence = str_pad($sequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '0001';
        }

        return "{$prefix}{$date}-{$newSequence}";
    }
}