<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\Concerns\CostsLineItems;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    use CostsLineItems;

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
        $this->assertStillOpen($opname);

        return DB::transaction(function () use ($opname, $counts) {
            foreach ($counts as $productId => $countedQty) {
                if ($countedQty === null || $countedQty === '') {
                    continue;
                }

                $item = StockOpnameItem::where('stock_opname_id', $opname->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($item) {
                    $variance = (float) $countedQty - $item->expected_qty;
                    $product = $item->product;
                    $item->update([
                        'counted_qty' => (float) $countedQty,
                        'variance' => $variance,
                        // The count screen has to show the number the
                        // completion journal will book: what the missing
                        // units cost through their batches, what the extra
                        // ones are worth at the buying price.
                        'variance_value' => $this->varianceValue($product, $variance),
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
        $this->assertStillOpen($opname);

        return DB::transaction(function () use ($opname) {
            $user = Auth::user();

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

                // Value what the count says is missing or extra, and keep
                // the item's figure identical to the journal below: a count
                // screen that shows one number and books another is exactly
                // the disagreement this service exists to prevent.
                $value = $this->varianceValue($product, (float) $item->variance, true);
                $item->update(['variance_value' => $value]);

                if ($item->variance > 0) {
                    $totalExcessValue += $value;
                } else {
                    $totalShrinkageValue += $value;
                }
            }

            // 3. Accounting Integration
            if ($totalShrinkageValue > 0 || $totalExcessValue > 0) {
                app(StockValuationService::class)->postCorrection(
                    $totalExcessValue,
                    $totalShrinkageValue,
                    "Stock Opname Variance #{$opname->reference_number}",
                    $opname
                );
            }

            $opname->update([
                'status' => 'Completed',
                'completed_at' => now(),
                // Both magnitudes are positive by construction (varianceValue
                // returns what the difference is worth, not its direction), so
                // their sum is the gross value the journal just moved. The
                // column was added for exactly this and had stayed null ever
                // since, which reads as "no variance" to anyone querying the
                // table instead of the view.
                'total_variance_value' => $totalExcessValue + $totalShrinkageValue,
            ]);

            return $opname;
        });
    }

    /**
     * Shut the door on a closed count.
     *
     * Completed has already moved stock and posted its journal; Canceled
     * was thrown away. The screen only hides the buttons for those two
     * states, so a stale tab or a direct POST would otherwise still land
     * here. The service is where the rule belongs: it is the one path
     * every caller shares.
     */
    private function assertStillOpen(StockOpname $opname): void
    {
        if ($opname->status === 'Completed') {
            throw new Exception('Stock Opname is already completed.');
        }

        if ($opname->status === 'Canceled') {
            throw new Exception('Stock Opname is canceled.');
        }
    }

    /**
     * What a count difference is worth, on the one cost basis every
     * inventory writer shares.
     *
     * Missing units are costed through the batches they came from, so the
     * figure equals what the Inventory account was charged for them; extra
     * units have no batch to belong to (a batch is a receipt the books
     * already know about) and are worth the buying price, the same
     * fallback a sale costs out-of-batch units at.
     *
     * $consume is true only when the opname completes and the goods really
     * have left the shelf: the count screen must show the very number the
     * journal will book, without touching the batches while the opname is
     * still open and can be recounted.
     */
    private function varianceValue(Product $product, float $variance, bool $consume = false): float
    {
        if (abs($variance) < 0.0000001) {
            return 0.0;
        }

        $units = abs($variance);
        $buyingPrice = (float) ($product->price ?? 0);

        if ($variance > 0) {
            return $units * $buyingPrice;
        }

        return $consume
            ? $this->costAndConsumeBatches((int) $product->id, $units, $buyingPrice)
            : app(StockValuationService::class)->costOfUnits((int) $product->id, $units, $buyingPrice);
    }

    /**
     * The journal completion would post, without posting it.
     *
     * Same arithmetic as completeOpname() with every write stripped out:
     * the variance values come from varianceValue() with consume=false,
     * which walks the same batches in the same order without decrementing
     * them, and the legs mirror StockValuationService::postCorrection()
     * in the same order and under the same gate it books them. The
     * confirm modal can therefore show the exact figure the confirm
     * click will post, and opening it cannot move stock or the GL.
     *
     * @return array{lines: array<int, array{account_name: string, type: string, amount: float}>, totals: array{excess: float, shrinkage: float}, will_post: bool}
     */
    public function previewCompletion(StockOpname $opname): array
    {
        $itemsWithVariance = $opname->items()->whereNotNull('variance')->where('variance', '!=', 0)->get();

        $totalShrinkageValue = 0;
        $totalExcessValue = 0;

        foreach ($itemsWithVariance as $item) {
            $value = $this->varianceValue($item->product, (float) $item->variance);

            if ($item->variance > 0) {
                $totalExcessValue += $value;
            } else {
                $totalShrinkageValue += $value;
            }
        }

        // The same gate postCorrection() applies before it books anything.
        $tenant = app('currentTenant');
        $settings = $tenant?->accounting_settings ?? [];
        $inventory = Account::find($settings['inventory_account_id'] ?? null);
        $expense = Account::find($settings['cost_of_goods_sold_account_id'] ?? null);

        $willPost = ($totalShrinkageValue > 0 || $totalExcessValue > 0)
            && $tenant
            && $tenant->hasFeature('accounting')
            && $inventory
            && $expense;

        $lines = [];

        if ($willPost) {
            // postCorrection() books the decrease first, then the increase.
            if ($totalShrinkageValue > 0) {
                $lines[] = ['account_name' => $expense->name, 'type' => 'debit', 'amount' => round($totalShrinkageValue, 2)];
                $lines[] = ['account_name' => $inventory->name, 'type' => 'credit', 'amount' => round($totalShrinkageValue, 2)];
            }

            if ($totalExcessValue > 0) {
                $lines[] = ['account_name' => $inventory->name, 'type' => 'debit', 'amount' => round($totalExcessValue, 2)];
                $lines[] = ['account_name' => $expense->name, 'type' => 'credit', 'amount' => round($totalExcessValue, 2)];
            }
        }

        return [
            'lines' => $lines,
            'totals' => [
                'excess' => round($totalExcessValue, 2),
                'shrinkage' => round($totalShrinkageValue, 2),
            ],
            'will_post' => (bool) $willPost,
        ];
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
