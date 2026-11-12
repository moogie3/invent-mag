<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stock half of the canceled-return correction; the GL half is
     * 2026_11_08_000001_reverse_journal_entries_of_canceled_returns.
     *
     * cancelSalesReturn()/cancelPurchaseReturn() only reverted stock and the
     * PO-remaining walk when the return was Completed, so every return
     * canceled from Pending kept the movement its creation had already made:
     * 41 Canceled returns (20 sales, 21 purchase) hold stock they should not
     * (52 units too many on the sales side, 111 too few on the purchase
     * side, which is what leaves product 28 / warehouse 17 at -4). The
     * service fix protects cancels from here on; this migration puts the
     * rows back.
     *
     * Each domain mirrors its cancel path exactly: per item row, the
     * product_warehouse row keyed on the sale's / PO's warehouse (the
     * service skips a missing product or stock row, so this does too), then
     * the same PO-remaining walk with the same filters and caps. Order
     * within the migration is deliberate: the PURCHASE walks run first, so
     * they see the pre-correction headroom (only purchase 61 / product 6
     * has any: 2 units against a need of 6); running them after the sales
     * walk would let them see headroom the sales walk had just created and
     * over-credit. Totals then land on the reviewed numbers: po_items
     * -52 (sales, fully covered everywhere) + 2 (purchase) = -50.
     *
     * po.status is deliberately NOT touched: the four POs whose status
     * disagrees with the live-return logic all have updated_at predating
     * their own returns, and 18 POs with no canceled return show the same
     * disagreement, so it is a property of the seeded rows, not of this
     * bug. Customer credits are not touched either: all 20 canceled sales
     * returns carry customer_credit_id NULL (they were never Completed),
     * verified live before writing. Sale status was already recomputed
     * unconditionally by the old cancel path, so it needs no correction.
     *
     * Re-runs are prevented by the migrations table (a data migration runs
     * once); selection reads the GL correction's own trail (see
     * correctedReturns()): only canceled returns whose entry that migration
     * reversed, so a return canceled from Completed, whose stock the old
     * gate already reverted, is never corrected twice on a database that
     * has one (this database has none: all 41 held a live entry before the
     * GL run, the signature of a cancel that never entered the gate). Run
     * it inside the deploy window, after 000001 (filename order guarantees
     * that) and before the fixed code serves traffic, so no cancel lands
     * in between. down() is the inverse: stock restores exactly (same
     * rows, opposite deltas); the PO-remaining walks run their opposite
     * direction, which restores exactly when nothing else moved in
     * between and no row sat below its cap before up() ran, and is bounded
     * best-effort otherwise (never below 0, never above quantity).
     */
    public function up(): void
    {
        DB::transaction(function () {
            $mainWarehouseId = DB::table('warehouses')->where('is_main', true)->value('id');

            // Purchase first: see the class note on walk ordering.
            $this->restorePurchaseStock($mainWarehouseId);
            $this->restoreSalesStock($mainWarehouseId);
        });
    }

    /**
     * Undo of up(): sales domain first (reverse order), stock exact, then
     * the PO walks in their opposite direction.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $mainWarehouseId = DB::table('warehouses')->where('is_main', true)->value('id');

            $this->unwindSalesStock($mainWarehouseId);
            $this->unwindPurchaseStock($mainWarehouseId);
        });
    }

    /**
     * Sales returns: stock came back IN at creation, so take it out again,
     * then run the same global earliest-expiry-first walk cancel runs.
     */
    private function restoreSalesStock(?int $mainWarehouseId): void
    {
        foreach ($this->correctedReturns('sales_returns', 'App\\Models\\SalesReturn') as $ret) {
            $warehouseId = DB::table('sales')->where('id', $ret->sales_id)->value('warehouse_id')
                ?? $mainWarehouseId;

            foreach ($this->returnItems('sales_return_items', 'sales_return_id', $ret->id) as $item) {
                if (! DB::table('products')->where('id', $item->product_id)->exists()) {
                    continue;
                }

                if ($warehouseId !== null) {
                    DB::table('product_warehouse')
                        ->where('product_id', $item->product_id)
                        ->where('warehouse_id', $warehouseId)
                        ->decrement('quantity', $item->quantity);
                }

                $this->decrementPOItemRemaining($item->product_id, $item->quantity);
            }
        }
    }

    /**
     * Purchase returns: stock went OUT at creation, so put it back, then
     * run the same per-PO walk cancel runs.
     */
    private function restorePurchaseStock(?int $mainWarehouseId): void
    {
        foreach ($this->correctedReturns('purchase_returns', 'App\\Models\\PurchaseReturn') as $ret) {
            $warehouseId = DB::table('po')->where('id', $ret->purchase_id)->value('warehouse_id')
                ?? $mainWarehouseId;

            foreach ($this->returnItems('purchase_return_items', 'purchase_return_id', $ret->id) as $item) {
                if (! DB::table('products')->where('id', $item->product_id)->exists()) {
                    continue;
                }

                if ($warehouseId !== null) {
                    DB::table('product_warehouse')
                        ->where('product_id', $item->product_id)
                        ->where('warehouse_id', $warehouseId)
                        ->increment('quantity', $item->quantity);
                }

                $this->incrementPOItemRemaining($ret->purchase_id, $item->product_id, $item->quantity);
            }
        }
    }

    /**
     * Mirror of SalesReturnService::decrementPOItemRemaining(): global
     * across the product, earliest expiry first, never below zero.
     */
    private function decrementPOItemRemaining(int $productId, int $quantity): void
    {
        $poItems = DB::table('po_items')
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($poItems as $poItem) {
            if ($quantity <= 0) {
                break;
            }
            $decrement = min($poItem->remaining_quantity, $quantity);
            DB::table('po_items')->where('id', $poItem->id)->decrement('remaining_quantity', $decrement);
            $quantity -= $decrement;
        }
    }

    /**
     * Mirror of PurchaseReturnService::incrementPOItemRemaining(): scoped
     * to the originating PO, only rows below their cap (which is what makes
     * the purchase side a near no-op here), never above quantity.
     */
    private function incrementPOItemRemaining(int $poId, int $productId, int $quantity): void
    {
        $poItems = DB::table('po_items')
            ->where('po_id', $poId)
            ->where('product_id', $productId)
            ->whereColumn('remaining_quantity', '<', 'quantity')
            ->get();

        foreach ($poItems as $poItem) {
            if ($quantity <= 0) {
                break;
            }
            $increment = min($poItem->quantity - $poItem->remaining_quantity, $quantity);
            DB::table('po_items')->where('id', $poItem->id)->increment('remaining_quantity', $increment);
            $quantity -= $increment;
        }
    }

    /**
     * Inverse of restoreSalesStock(): stock exact; the walk fills the rows
     * up() drained first (earliest expiry, below cap), bounded by the cap.
     */
    private function unwindSalesStock(?int $mainWarehouseId): void
    {
        foreach ($this->correctedReturns('sales_returns', 'App\\Models\\SalesReturn') as $ret) {
            $warehouseId = DB::table('sales')->where('id', $ret->sales_id)->value('warehouse_id')
                ?? $mainWarehouseId;

            foreach ($this->returnItems('sales_return_items', 'sales_return_id', $ret->id) as $item) {
                if (! DB::table('products')->where('id', $item->product_id)->exists()) {
                    continue;
                }

                if ($warehouseId !== null) {
                    DB::table('product_warehouse')
                        ->where('product_id', $item->product_id)
                        ->where('warehouse_id', $warehouseId)
                        ->increment('quantity', $item->quantity);
                }

                $this->refillPOItemRemaining($item->product_id, $item->quantity);
            }
        }
    }

    /**
     * Inverse of restorePurchaseStock(): stock exact; the walk takes back
     * from the rows up() filled (earliest expiry first, never below zero).
     */
    private function unwindPurchaseStock(?int $mainWarehouseId): void
    {
        foreach ($this->correctedReturns('purchase_returns', 'App\\Models\\PurchaseReturn') as $ret) {
            $warehouseId = DB::table('po')->where('id', $ret->purchase_id)->value('warehouse_id')
                ?? $mainWarehouseId;

            foreach ($this->returnItems('purchase_return_items', 'purchase_return_id', $ret->id) as $item) {
                if (! DB::table('products')->where('id', $item->product_id)->exists()) {
                    continue;
                }

                if ($warehouseId !== null) {
                    DB::table('product_warehouse')
                        ->where('product_id', $item->product_id)
                        ->where('warehouse_id', $warehouseId)
                        ->decrement('quantity', $item->quantity);
                }

                $this->drainPOItemRemaining($ret->purchase_id, $item->product_id, $item->quantity);
            }
        }
    }

    /** Inverse walk for the sales side: refill drained rows, cap-bounded. */
    private function refillPOItemRemaining(int $productId, int $quantity): void
    {
        $poItems = DB::table('po_items')
            ->where('product_id', $productId)
            ->whereColumn('remaining_quantity', '<', 'quantity')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($poItems as $poItem) {
            if ($quantity <= 0) {
                break;
            }
            $increment = min($poItem->quantity - $poItem->remaining_quantity, $quantity);
            DB::table('po_items')->where('id', $poItem->id)->increment('remaining_quantity', $increment);
            $quantity -= $increment;
        }
    }

    /** Inverse walk for the purchase side: take back, never below zero. */
    private function drainPOItemRemaining(int $poId, int $productId, int $quantity): void
    {
        $poItems = DB::table('po_items')
            ->where('po_id', $poId)
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->get();

        foreach ($poItems as $poItem) {
            if ($quantity <= 0) {
                break;
            }
            $decrement = min($poItem->remaining_quantity, $quantity);
            DB::table('po_items')->where('id', $poItem->id)->decrement('remaining_quantity', $decrement);
            $quantity -= $decrement;
        }
    }

    /**
     * Returns the GL correction marked: canceled returns carrying an entry
     * that 2026_11_08_000001 reversed, identified by the correction marker
     * in the reversal's notes. That population is exactly "canceled while
     * still holding the movement creation made": the old gate reverted
     * stock and the GL in the SAME branch, so an entry left live means
     * stock was left moved, while a return canceled from Completed carries
     * a reversal written by the cancel itself, without the marker, and is
     * never touched twice. down() reads the same set (rollback runs 000002
     * before 000001, so the markers still exist when it executes). A
     * canceled return with no journal entry at all carries no marker and
     * is left alone: nothing proves its stock was ever moved.
     */
    private function correctedReturns(string $table, string $sourceableType)
    {
        return DB::table($table)
            ->where('status', 'Canceled')
            ->whereExists(function ($query) use ($table, $sourceableType) {
                $query->selectRaw('1')
                    ->from('journal_entries as entries')
                    ->join('journal_entries as reversals', 'reversals.reversed_entry_id', '=', 'entries.id')
                    ->whereColumn('entries.sourceable_id', "{$table}.id")
                    ->where('entries.sourceable_type', $sourceableType)
                    ->where('reversals.notes', 'like', '%2026-11-08 data correction%');
            })
            ->orderBy('id')
            ->get();
    }

    /** Item rows of one return, stable order. */
    private function returnItems(string $table, string $foreignKey, int $returnId)
    {
        return DB::table($table)->where($foreignKey, $returnId)->orderBy('id')->get();
    }
};
