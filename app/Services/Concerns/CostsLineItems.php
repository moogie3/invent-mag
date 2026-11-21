<?php

namespace App\Services\Concerns;

use App\Models\POItem;
use App\Services\StockValuationService;

/**
 * One shared implementation of FIFO batch costing for sold lines.
 *
 * Why a trait and not a copy-pasted block per service: a line has to cost the
 * same amount no matter which screen rang it up. SalesService carried three
 * copies of this loop (createSale, finalizeConvertedSale, updateSale) and
 * PosService a fourth that read a product field which does not exist, so the
 * point-of-sale booked zero cost on every transaction while the invoice
 * screens booked the real figure. Centralising it puts the batch order, the
 * expiry-first tie-break and the fallback cost in exactly one place, where
 * they cannot drift apart again.
 *
 * Usage:
 *
 *     $totalCostOfGoods += $this->costAndConsumeBatches($product->id, $quantity, $product->price);
 *
 * Batches are consumed as they are costed, in the order a regular sale has
 * always used: earliest expiry first, then oldest receipt. When the batches
 * run dry the caller's unit cost is used, which is the product's buying price.
 *
 * What a batch costs is not its raw line price: a purchase's Inventory
 * debit was reduced by the order discount and by any recoverable input VAT,
 * so costing at the raw price would credit back more than the ledger ever
 * held. StockValuationService::unitCost() carries that one arithmetic for
 * every writer (sales, opname, manual corrections), which is what keeps the
 * Inventory account and the shelf on the same number.
 */
trait CostsLineItems
{
    /**
     * Cost the units on one line, consuming purchase batches as it goes.
     *
     * @param  int  $productId  the product being sold
     * @param  int|float  $quantity  units on this line
     * @param  float  $fallbackUnitCost  what one unit costs when no batch is left
     * @return float the cost of these units, for the COGS debit
     */
    protected function costAndConsumeBatches(int $productId, int|float $quantity, float $fallbackUnitCost): float
    {
        $poItems = POItem::where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Resolved once per line so every batch of one line shares the same
        // memo of purchase capitalization factors.
        $valuation = app(StockValuationService::class);

        $remaining = $quantity;
        $cost = 0.0;

        foreach ($poItems as $poItem) {
            if ($remaining <= 0) {
                break;
            }

            $decrement = min($poItem->remaining_quantity, $remaining);
            $cost += $decrement * $valuation->unitCost($poItem);
            $poItem->decrement('remaining_quantity', $decrement);
            $remaining -= $decrement;
        }

        if ($remaining > 0) {
            $cost += $remaining * $fallbackUnitCost;
        }

        return $cost;
    }
}
