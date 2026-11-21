<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Helpers\PurchaseHelper;
use App\Helpers\ReturnTaxHelper;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\User;
use App\Services\Concerns\BuildsListSearch;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    use BuildsListSearch;

    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    private function decrementPOItemRemaining($poId, $productId, $quantity)
    {
        // Explicit FEFO order: SQLite happens to hand these back in expiry
        // order because of the (po_id, expiry_date) index, but that is a
        // coincidence of one query plan. MariaDB in production may pick the
        // product_id or primary index instead and return the lines in a
        // different order, which would drain a different PO line for the same
        // return (audit F6). id is the tie-breaker for lines sharing an
        // expiry and a write timestamp.
        $poItems = POItem::where('po_id', $poId)
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($poItems as $poItem) {
            if ($quantity <= 0) {
                break;
            }
            $decrement = min($poItem->remaining_quantity, $quantity);
            $poItem->decrement('remaining_quantity', $decrement);
            $quantity -= $decrement;
        }
    }

    private function incrementPOItemRemaining($poId, $productId, $quantity)
    {
        // Same FEFO order as the draw above (audit F6), so refunding walks
        // the lines in a fixed sequence rather than in table order.
        $poItems = POItem::where('po_id', $poId)
            ->where('product_id', $productId)
            ->whereColumn('remaining_quantity', '<', 'quantity')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($poItems as $poItem) {
            if ($quantity <= 0) {
                break;
            }
            $availableSpace = $poItem->quantity - $poItem->remaining_quantity;
            $increment = min($availableSpace, $quantity);
            $poItem->increment('remaining_quantity', $increment);
            $quantity -= $increment;
        }
    }

    public function getPurchaseReturnIndexData(array $filters, int $entries)
    {
        $query = PurchaseReturn::with(['purchase', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('return_date', $filters['month']);
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('return_date', $filters['year']);
        }

        // Whole-catalog search across the return itself, the originating
        // purchase and that purchase's supplier.
        $this->applyListSearch($query, $filters, ['purchase_returns.reason', 'purchase_returns.status'], [
            'purchase' => ['po.invoice'],
            'purchase.supplier' => ['suppliers.name'],
        ]);

        $returns = $query->paginate($entries);

        $statusCounts = PurchaseReturn::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'returns' => $returns,
            'total_returns' => $returns->total(),
            'total_amount' => PurchaseReturn::sum('total_amount'),
            'completed_count' => $statusCounts->get('Completed', 0),
            'pending_count' => $statusCounts->get('Pending', 0),
            'canceled_count' => $statusCounts->get('Canceled', 0),
            'entries' => $entries,
        ];
    }

    /**
     * Refuse a return that asks for more than this purchase ever sold.
     *
     * Per product: what the PO lines ordered, minus every LIVE (non-Canceled)
     * return already on file, minus on edit the return being edited (its own
     * old units are being replaced right now). Without this check the stock
     * record could go negative, the purchase flipped to Returned and the
     * journal reversed more than the purchase ever booked, and the form
     * offered quantities a Pending return had already taken.
     *
     * @param  array<int|string, array<string, mixed>>  $returnedItems
     *
     * @throws \Exception when the request passes what can still be returned.
     */
    public function assertReturnable(Purchase $purchase, array $returnedItems, ?PurchaseReturn $excluding = null): void
    {
        $orderedByProduct = $purchase->items()
            ->get()
            ->groupBy('product_id')
            ->map(fn ($lines) => $lines->sum('quantity'));

        $liveByProduct = PurchaseReturn::query()
            ->where('purchase_id', $purchase->id)
            ->when($excluding, fn ($query) => $query->where('id', '!=', $excluding->id))
            ->where('status', '!=', 'Canceled')
            ->with('items')
            ->get()
            ->flatMap(fn ($return) => $return->items ?? collect())
            ->groupBy('product_id')
            ->map(fn ($items) => $items->sum('quantity'));

        foreach ($returnedItems as $item) {
            $quantity = (float) ($item['returned_quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $productId = $item['product_id'] ?? null;
            $ordered = (int) ($orderedByProduct[$productId] ?? 0);
            $alreadyLive = (int) ($liveByProduct[$productId] ?? 0);
            $available = $ordered - $alreadyLive;

            if ($quantity > $available) {
                $name = Product::find($productId)?->name ?? ('#'.$productId);
                $detail = $ordered > 0
                    ? "only {$available} of the {$ordered} purchased units can still be returned"
                    : 'this product is not on the purchase';

                throw new \Exception("Cannot return {$quantity} of \"{$name}\": {$detail}.");
            }
        }
    }

    public function createPurchaseReturn(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::findOrFail($data['purchase_id']);
            $items = json_decode($data['items'], true);
            if (! $items || ! is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            // Filter for items that are actually being returned
            $returnedItems = array_filter($items, function ($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            if (empty($returnedItems)) {
                throw new \Exception('No items selected for return.');
            }

            // A return born Canceled holds no stock and no live journal entry:
            // cancel gives both back, so creating one with them would leave the
            // row in the state cancel exists to prevent. The form no longer
            // offers Canceled; this backstop covers seeders, factories and any
            // caller that bypasses it.
            $isCanceled = $data['status'] === 'Canceled';

            // Refused before anything is written: over-returning would drive
            // the stock record negative, mark the purchase Returned and reverse
            // more of it than was ever booked. A Canceled birth holds nothing,
            // so it cannot over-return.
            if (! $isCanceled) {
                $this->assertReturnable($purchase, $returnedItems);
            }

            // Recalculate total amount on the backend to ensure data integrity
            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['return_price'] ?? $itemData['price']) * $itemData['returned_quantity'];
            }

            // Copy tax information from the purchase.
            // Compute whenever rate > 0 — tax_id may be null for legacy records.
            $taxRate = (float) ($purchase->tax_rate ?? 0);
            $taxType = $purchase->tax_type ?? 'none';
            $taxAmount = $taxRate > 0
                ? PurchaseHelper::calculateTaxAmount($totalReturnAmount, $taxRate, $taxType)
                : 0.0;

            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'user_id' => Auth::id(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
                'tax_id' => $purchase->tax_id,
                'tax_rate' => $purchase->tax_rate ?? 0,
                'tax_amount' => $taxAmount,
                'tax_type' => $purchase->tax_type ?? 'none',
            ]);

            // A return born Canceled holds no stock and no live journal entry
            // (see $isCanceled above): the stock move below is skipped so the
            // move cancel would have to undo is never made in the first place.
            foreach ($returnedItems as $itemData) {
                $returnPrice = $itemData['return_price'] ?? $itemData['price'];
                $returnedQuantity = $itemData['returned_quantity'];

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $returnPrice,
                    'total' => $returnPrice * $returnedQuantity,
                ]);

                // Decrement stock for the returned product. A Canceled return
                // moves no stock, so the move cancel would have to undo is
                // never made in the first place.
                $product = Product::find($itemData['product_id']);
                if ($product && ! $isCanceled) {
                    $warehouseId = $purchase->warehouse_id ?? Product::getMainWarehouseId();

                    $stockRecord = ProductWarehouse::where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->first();

                    if ($stockRecord) {
                        $stockRecord->decrement('quantity', $returnedQuantity);
                    }
                    $this->decrementPOItemRemaining($data['purchase_id'], $product->id, $returnedQuantity);
                }
            }

            // Update original purchase status
            $totalPurchasedQuantity = $purchase->items->sum('quantity');
            // Only LIVE returns count: a canceled return keeps its rows for
            // history but has given its stock and its journal entry back, so it
            // cannot keep the purchase marked Returned. Same rule the edit and
            // cancel paths already apply.
            $totalReturnedQuantity = $purchase->purchaseReturns()
                ->where('status', '!=', 'Canceled')
                ->with('items')->get()->flatMap(function ($pr) {
                    return $pr->items;
                })->sum('quantity');

            if ($totalReturnedQuantity >= $totalPurchasedQuantity) {
                $purchase->update(['status' => 'Returned']);
            } elseif ($totalReturnedQuantity > 0) {
                $purchase->update(['status' => 'Partial']);
            }

            if (! $isCanceled) {
                $this->createPurchaseReturnJournalEntry($purchaseReturn, $purchase);
            }

            return $purchaseReturn;
        });
    }

    /**
     * The proportional reversal a return against this purchase will book.
     *
     * Single source of truth: createPurchaseReturnJournalEntry() and the
     * return screens both read this, so a figure shown to the user cannot
     * drift from the figure posted to the ledger.
     *
     * Unlike a sale (whose tax comes from its snapshot), the bases here are
     * what the original journal ACTUALLY posted: legacy and fallback
     * postings may differ from the purchase row, and the ledger is what
     * gets reversed.
     *
     * @return array{ratio: float, ppn: float, pph: float, purchase: float}
     */
    public function deriveReversal(Purchase $purchase, float $returnTotal): array
    {
        $accountingSettings = app('currentTenant')->accounting_settings ?? [];

        $inventoryAccount = Account::find($accountingSettings['inventory_account_id'] ?? null);
        $ppnMasukanAccount = Account::find($accountingSettings['ppn_masukan_account_id'] ?? null);
        $pphPayableAccount = Account::find($accountingSettings['pph_payable_account_id'] ?? null);

        // Without the inventory debit there is no base to prorate from, so
        // every derived amount falls to 0: nothing booked, nothing shown.
        if (! $inventoryAccount) {
            return ['ratio' => 0.0, 'ppn' => 0.0, 'pph' => 0.0, 'purchase' => 0.0];
        }

        // Read the original journal through the account relation:
        // transactions store account_id, and Transaction has no
        // account_name attribute. Reading it directly always yielded
        // null, so the PPN Masukan/PPh Payable reversals (and the
        // proportional COGS lines) never fired.
        $originalJournalEntries = JournalEntry::where('sourceable_type', Purchase::class)
            ->where('sourceable_id', $purchase->id)
            ->where('status', 'posted')
            ->with('transactions.account')
            ->get();

        $originalPurchaseAmount = 0;
        $originalPpnMasukanAmount = 0;
        $originalPphPayableAmount = 0;

        foreach ($originalJournalEntries as $journalEntry) {
            foreach ($journalEntry->transactions as $transaction) {
                if ($originalPurchaseAmount === 0 && $transaction->account?->name === $inventoryAccount->name && $transaction->type === 'debit') {
                    $originalPurchaseAmount = $transaction->amount;
                }
                // What the original purchase actually booked to input VAT
                // and withholding: reversed proportionally further down.
                if ($ppnMasukanAccount && $originalPpnMasukanAmount === 0 && $transaction->account?->name === $ppnMasukanAccount->name && $transaction->type === 'debit') {
                    $originalPpnMasukanAmount = $transaction->amount;
                }
                if ($pphPayableAccount && $originalPphPayableAmount === 0 && $transaction->account?->name === $pphPayableAccount->name && $transaction->type === 'credit') {
                    $originalPphPayableAmount = $transaction->amount;
                }
            }
        }

        $reversal = ReturnTaxHelper::purchaseReversal(
            $returnTotal,
            $originalPurchaseAmount,
            $originalPpnMasukanAmount,
            $originalPphPayableAmount,
        );

        $reversal['purchase'] = $originalPurchaseAmount * $reversal['ratio'];

        return $reversal;
    }

    /**
     * Post the GL entry for a purchase return: Dr AP / Cr Purchase Returns
     * (contra-COGS) for the return total, plus the proportional COGS, PPN
     * Masukan and PPh Payable reversals implied by the original purchase's
     * journal. Called on create, and on update after the previous version has
     * been reversed, so edits re-journal with the new totals and date.
     */
    private function createPurchaseReturnJournalEntry(PurchaseReturn $purchaseReturn, Purchase $purchase): void
    {
        $totalReturnAmount = (float) $purchaseReturn->total_amount;

        // Create Journal Entry for the return
        $accountingSettings = app('currentTenant')->accounting_settings ?? [];
        if (! $accountingSettings || ! isset($accountingSettings['inventory_account_id']) || ! isset($accountingSettings['accounts_payable_account_id'])) {
            throw new \Exception('Accounting settings for inventory or accounts payable are not configured.');
        }

        $inventoryAccount = Account::find($accountingSettings['inventory_account_id'] ?? null);
        $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id'] ?? null);
        $purchaseAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id'] ?? null); // Use COGS account for Purchase
        // Optional tax accounts: only used to reverse what the original
        // purchase actually posted (see below).
        $ppnMasukanAccount = Account::find($accountingSettings['ppn_masukan_account_id'] ?? null);
        $pphPayableAccount = Account::find($accountingSettings['pph_payable_account_id'] ?? null);

        if (! $inventoryAccount || ! $accountsPayableAccount || ! $purchaseAccount) {
            throw new \Exception('Required accounts (Inventory, Accounts Payable, Purchases) not found for Purchase Return. Please check Accounting Settings.');
        }

        // The scan, the ratio and both tax reversals come from one shared
        // derivation, so a figure shown on the return screens can never
        // drift from the figure booked here.
        $reversal = $this->deriveReversal($purchase, $totalReturnAmount);
        $proportionalPurchase = $reversal['purchase'];

        $inventoryAccountName = $inventoryAccount->name;
        $accountsPayableAccountName = $accountsPayableAccount->name;
        $purchaseAccountName = $purchaseAccount->name;

        $purchaseReturnsAccount = Account::where('code', 'like', '5120-%')->first();
        $purchaseReturnsAccountName = $purchaseReturnsAccount?->name ?? $purchaseAccountName;

        // Create comprehensive journal entry for purchase return
        // Debit AP (reduce what we owe), Credit Purchase Returns (contra-COGS)
        $transactions = [
            // Reduce Accounts Payable (we no longer owe this much)
            ['account_name' => $accountsPayableAccountName, 'type' => 'debit', 'amount' => $totalReturnAmount],
            // Record purchase return (contra-COGS)
            ['account_name' => $purchaseReturnsAccountName, 'type' => 'credit', 'amount' => $totalReturnAmount],
            // Reverse Purchase/COGS (reduce expense)
            ['account_name' => $purchaseAccountName, 'type' => 'debit', 'amount' => $proportionalPurchase],
            // Reduce Inventory (items returned from stock)
            ['account_name' => $inventoryAccountName, 'type' => 'credit', 'amount' => $proportionalPurchase],
        ];

        // PPN Masukan reversal: the original purchase debited the input
        // VAT asset (Dr Inventory net / Dr PPN Masukan / Cr AP gross), so
        // a return credits PPN Masukan back proportionally and debits AP
        // for the VAT portion no longer owed: the mirror image of the
        // PPN Keluaran reversal in SalesReturnService. Without this, a
        // returned purchase left the input VAT asset (and the payable
        // reduction) permanently overstated. Only reverses what the
        // original journal actually posted.
        if ($reversal['ppn'] > 0) {
            // Cr. PPN Masukan (reduce input VAT asset)
            $transactions[] = ['account_name' => $ppnMasukanAccount->name, 'type' => 'credit', 'amount' => $reversal['ppn']];
            // Dr. AP (this VAT is no longer owed to the supplier)
            $transactions[] = ['account_name' => $accountsPayableAccountName, 'type' => 'debit', 'amount' => $reversal['ppn']];
        }

        // PPh Payable reversal: the original purchase credited the
        // withholding liability (less was withheld from the supplier
        // payment), so a return debits it back and credits AP for the
        // amount owed again.
        if ($reversal['pph'] > 0) {
            // Dr. PPh Payable (reverse the withholding liability)
            $transactions[] = ['account_name' => $pphPayableAccount->name, 'type' => 'debit', 'amount' => $reversal['pph']];
            // Cr. AP (the withheld amount is owed again)
            $transactions[] = ['account_name' => $accountsPayableAccountName, 'type' => 'credit', 'amount' => $reversal['pph']];
        }

        $this->accountingService->createJournalEntry(
            "Purchase Return for PO #{$purchase->invoice}",
            Carbon::parse($purchaseReturn->return_date),
            $transactions,
            $purchaseReturn
        );
    }

    /**
     * Reverse this return's posted journal entries. Entries a prior reversal
     * already netted out are skipped: reverseJournalEntry() leaves the
     * original status 'posted' (the link lives on the reversal's
     * reversed_entry_id), so without the guard a second edit, or an edit
     * followed by delete/cancel, would reverse the same entry twice and
     * corrupt the GL in the opposite direction.
     */
    private function reversePurchaseReturnJournalEntries(PurchaseReturn $purchaseReturn, string $note): void
    {
        $journalEntries = JournalEntry::where('sourceable_type', get_class($purchaseReturn))
            ->where('sourceable_id', $purchaseReturn->id)
            ->get();

        foreach ($journalEntries as $entry) {
            if ($entry->canBeReversed() && ! $entry->hasDependentEntries()) {
                $this->accountingService->reverseJournalEntry($entry, $note);
            }
        }
    }

    public function updatePurchaseReturn(PurchaseReturn $purchaseReturn, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn, $data) {
            $items = json_decode($data['items'], true);
            if (! $items || ! is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            // Both sides of the status change, captured before anything is
            // written: stock may only move for a return that HELD stock (was
            // live) into one that will HOLD stock (stays live). A Canceled
            // return holds nothing, so each half is skipped accordingly.
            $wasCanceled = $purchaseReturn->status === 'Canceled';
            $isCanceled = $data['status'] === 'Canceled';

            // Filter for items that are actually being returned: hoisted above
            // every write so the cap can be checked before anything moves.
            $returnedItems = array_filter($items, function ($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            // Refused before the stock revert: the edited return's own old
            // units are excluded (they are being replaced right now), the other
            // live returns are not. A Canceled destination holds nothing, so it
            // cannot over-return either.
            if (! $isCanceled) {
                $this->assertReturnable($purchaseReturn->purchase, $returnedItems, $purchaseReturn);
            }

            // Revert old stock quantities before deleting - but only for a
            // return that actually holds them: a Canceled one already gave its
            // stock back on the way to Canceled, and incrementing here would
            // put the same goods into the warehouse a second time.
            if (! $wasCanceled) {
                foreach ($purchaseReturn->items as $oldItem) {
                    $product = Product::find($oldItem->product_id);
                    if ($product) {
                        $purchase = $purchaseReturn->purchase;
                        $warehouseId = $purchase->warehouse_id ?? Product::getMainWarehouseId();

                        $stockRecord = ProductWarehouse::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        if ($stockRecord) {
                            $stockRecord->increment('quantity', $oldItem->quantity);
                        }
                        $this->incrementPOItemRemaining($purchase->id, $product->id, $oldItem->quantity);
                    }
                }
            }
            $purchaseReturn->items()->delete();

            // Recalculate total amount on the backend
            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['return_price'] ?? $itemData['price'] ?? 0) * $itemData['returned_quantity'];
            }

            $purchase = $purchaseReturn->purchase;
            $updatedTaxAmount = 0;
            $updatedTaxRate = (float) ($purchase->tax_rate ?? 0);
            $updatedTaxType = $purchase->tax_type ?? 'none';
            if ($updatedTaxRate > 0) {
                $updatedTaxAmount = PurchaseHelper::calculateTaxAmount(
                    $totalReturnAmount,
                    $updatedTaxRate,
                    $updatedTaxType
                );
            }

            $purchaseReturn->update([
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
                'user_id' => Auth::id(),
                'tax_id' => $purchase ? $purchase->tax_id : $purchaseReturn->tax_id,
                'tax_rate' => $updatedTaxRate,
                'tax_amount' => $updatedTaxAmount,
                'tax_type' => $updatedTaxType,
            ]);

            foreach ($returnedItems as $itemData) {
                $returnedQuantity = $itemData['returned_quantity'];
                $price = $itemData['return_price'] ?? $itemData['price'] ?? 0;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $price,
                    'total' => $price * $returnedQuantity,
                ]);

                // A return edited to Canceled must not keep taking goods out
                // of the warehouse: the item rows are still written for
                // history, only the stock move is skipped - the same guard
                // createPurchaseReturn() applies to a return born Canceled.
                $product = Product::find($itemData['product_id']);
                if ($product && ! $isCanceled) {
                    $purchase = $purchaseReturn->purchase;
                    $warehouseId = $purchase->warehouse_id ?? Product::getMainWarehouseId();

                    $stockRecord = ProductWarehouse::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $warehouseId, 'tenant_id' => $product->tenant_id],
                        ['quantity' => 0]
                    );
                    $stockRecord->decrement('quantity', $returnedQuantity);
                    $this->decrementPOItemRemaining($purchase->id, $product->id, $returnedQuantity);
                }
            }
            $purchase = $purchaseReturn->purchase;
            // Update original purchase status
            $totalPurchasedQuantity = $purchase->items()->sum('quantity');
            // Only LIVE returns count: a canceled return still holds its rows
            // (cancel keeps them for history) but has given its stock and its
            // journal entry back, so it cannot keep the purchase marked Returned.
            $totalReturnedQuantity = $purchase->purchaseReturns()
                ->where('status', '!=', 'Canceled')
                ->with('items')->get()->flatMap(function ($pr) {
                    return $pr->items;
                })->sum('quantity');

            if ($totalReturnedQuantity >= $totalPurchasedQuantity) {
                $purchase->update(['status' => 'Returned']);
            } elseif ($totalReturnedQuantity > 0) {
                $purchase->update(['status' => 'Partial']); // Reverted to 'Partial'
            } else {
                // You might want to revert to the original purchase status if all returns are deleted.
                // This depends on business logic. For now, we leave it.
            }

            // Re-journal so the GL reflects the edit: reverse whatever the
            // previous version posted (entries a prior reversal already netted
            // out are skipped), then post the updated entry with the new
            // totals and date. A canceled return keeps the reversal only, and
            // a zeroed-out return nets to zero instead of posting an empty
            // entry.
            $this->reversePurchaseReturnJournalEntries(
                $purchaseReturn,
                "Reversal due to edited Purchase Return #{$purchaseReturn->id}"
            );

            if ($purchaseReturn->status !== 'Canceled' && (float) $purchaseReturn->total_amount > 0) {
                $this->createPurchaseReturnJournalEntry($purchaseReturn, $purchase);
            }

            return $purchaseReturn->fresh(); // Return the updated model with relations
        });
    }

    public function bulkExportPurchaseReturns(array $filters, ?array $ids, string $exportOption)
    {
        $query = PurchaseReturn::with(['purchase', 'user']);

        if ($ids) {
            $query->whereIn('id', $ids);
        } else {
            if (isset($filters['month']) && $filters['month']) {
                $query->whereMonth('return_date', $filters['month']);
            }
            if (isset($filters['year']) && $filters['year']) {
                $query->whereYear('return_date', $filters['year']);
            }
        }

        $purchaseReturns = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.por.bulk-export-pdf', compact('purchaseReturns'))->render();
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->stream('purchase-returns.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=purchase-returns.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($purchaseReturns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Purchase Invoice',
                    'Return Date',
                    'Total Amount',
                    'Status',
                    'Returned By',
                ]);

                foreach ($purchaseReturns as $pr) {
                    fputcsv($file, [
                        $pr->purchase->invoice,
                        Carbon::parse($pr->return_date)->format('Y-m-d'),
                        CurrencyHelper::format($pr->total_amount),
                        $pr->status,
                        $pr->user->name,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function deletePurchaseReturn(PurchaseReturn $purchaseReturn): void
    {
        DB::transaction(function () use ($purchaseReturn) {
            // Reverse associated journal entries for accurate accounting
            $this->reversePurchaseReturnJournalEntries($purchaseReturn, "Auto-reversal due to deleted Purchase Return #{$purchaseReturn->id}");

            // A Canceled return already gave its stock back on the way to
            // Canceled, so delete only removes the row: reverting here moved
            // the same goods a second time and left the warehouse holding
            // more than the purchase ever shipped. The journal reversal above
            // stays unconditional because its shared guard makes it a no-op
            // for an entry cancel already netted out.
            $isCanceled = $purchaseReturn->status === 'Canceled';

            $purchase = $purchaseReturn->purchase;
            if ($purchase && ! $isCanceled) {
                foreach ($purchaseReturn->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $warehouseId = $purchase->warehouse_id ?? Product::getMainWarehouseId();

                        $stockRecord = ProductWarehouse::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouseId)
                            ->where('tenant_id', $product->tenant_id)
                            ->first();

                        if ($stockRecord) {
                            $stockRecord->increment('quantity', $item->quantity);
                        }
                        $this->incrementPOItemRemaining($purchaseReturn->purchase_id, $product->id, $item->quantity);
                    }
                }
            }

            $purchaseReturn->delete();

            if ($purchase) {
                $totalPurchasedQuantity = $purchase->items()->sum('quantity');
                // Only LIVE returns count, same as create, update and cancel:
                // a canceled row keeps its items for history but has given its
                // stock back, so counting it here would leave the purchase on
                // Partial with nothing actually returned.
                $totalQuantityReturnedSoFar = $purchase->purchaseReturns()
                    ->where('status', '!=', 'Canceled')
                    ->with('items')
                    ->get()
                    ->flatMap(function ($pr) {
                        return $pr->items;
                    })
                    ->sum('quantity');

                if ($totalQuantityReturnedSoFar <= 0) {
                    if ($purchase->total_paid >= $purchase->total) {
                        $purchase->update(['status' => 'Paid']);
                    } elseif ($purchase->total_paid > 0) {
                        $purchase->update(['status' => 'Partial']);
                    } else {
                        $purchase->update(['status' => 'Unpaid']);
                    }
                } elseif ($totalQuantityReturnedSoFar < $totalPurchasedQuantity) {
                    $purchase->update(['status' => 'Partial']);
                }
            }
        });
    }

    public function bulkDeletePurchaseReturns(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $purchaseReturns = PurchaseReturn::whereIn('id', $ids)->with('items')->get();
            foreach ($purchaseReturns as $pr) {
                $this->deletePurchaseReturn($pr);
            }
        });
    }

    /**
     * Flip a live purchase return to Completed. Completion carries no stock
     * or journal work of its own: createPurchaseReturn() moves the stock and
     * books the entry at birth for every live status, exactly as the sales
     * side does, so what is left here is the workflow flag that completion
     * has always been on the purchase side. A Canceled row holds nothing, so
     * completing it is refused.
     */
    public function completePurchaseReturn(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->status === 'Completed') {
            return;
        }

        // Mirror of completeSalesReturn(): a canceled return already gave its
        // stock back and reversed its journal entry, so completing it would
        // mark a dead row as live. The bulk action skips these rows; this
        // throw is the server-side backstop for direct callers.
        if ($purchaseReturn->status === 'Canceled') {
            throw new \Exception('A canceled purchase return cannot be completed.');
        }

        DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn->update(['status' => 'Completed']);
        });
    }

    /**
     * Mark the selected purchase returns Completed, one row at a time. A
     * Canceled row is skipped rather than failed, and the counts come back
     * so the caller can tell the user exactly what happened.
     *
     * @param  array<int, int>  $ids
     * @return array{completed: int, already_completed: int, skipped: int, completed_ids: array<int, int>}
     */
    public function bulkCompletePurchaseReturns(array $ids): array
    {
        $counts = ['completed' => 0, 'already_completed' => 0, 'skipped' => 0, 'completed_ids' => []];

        $purchaseReturns = PurchaseReturn::whereIn('id', $ids)->get();
        foreach ($purchaseReturns as $purchaseReturn) {
            if ($purchaseReturn->status === 'Canceled') {
                $counts['skipped']++;

                continue;
            }

            if ($purchaseReturn->status === 'Completed') {
                $counts['already_completed']++;

                continue;
            }

            $this->completePurchaseReturn($purchaseReturn);
            $counts['completed']++;
            $counts['completed_ids'][] = $purchaseReturn->id;
        }

        return $counts;
    }

    public function cancelPurchaseReturn(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->status === 'Canceled') {
            return;
        }

        DB::transaction(function () use ($purchaseReturn) {
            // Revert for EVERY live status, not just Completed - mirror of
            // cancelSalesReturn() and of deletePurchaseReturn(), which already
            // reverts unconditionally. createPurchaseReturn() moves the stock and
            // posts the journal at birth, so canceling from Pending has to give
            // both back; the Completed-only gate leaked them and left a live GL
            // entry against a canceled return.
            $purchase = $purchaseReturn->purchase;

            foreach ($purchaseReturn->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $warehouseId = $purchase->warehouse_id ?? Product::getMainWarehouseId();
                    $stockRecord = ProductWarehouse::where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->first();
                    if ($stockRecord) {
                        $stockRecord->increment('quantity', $item->quantity);
                    }
                    $this->incrementPOItemRemaining($purchase->id, $product->id, $item->quantity);
                }
            }

            $this->reversePurchaseReturnJournalEntries($purchaseReturn, "Reversal due to canceled Purchase Return #{$purchaseReturn->id}");

            $purchaseReturn->update(['status' => 'Canceled']);

            $purchase = $purchaseReturn->purchase;
            if ($purchase) {
                $totalPurchasedQuantity = $purchase->items()->sum('quantity');
                // Only LIVE returns count: cancel keeps the canceled row for
                // history, and counting it here would leave the purchase on
                // Partial with nothing actually returned.
                $totalQuantityReturnedSoFar = $purchase->purchaseReturns()
                    ->where('status', '!=', 'Canceled')
                    ->with('items')->get()->flatMap(function ($pr) {
                        return $pr->items;
                    })->sum('quantity');

                if ($totalQuantityReturnedSoFar <= 0) {
                    if ($purchase->total_paid >= $purchase->total) {
                        $purchase->update(['status' => 'Paid']);
                    } elseif ($purchase->total_paid > 0) {
                        $purchase->update(['status' => 'Partial']);
                    } else {
                        $purchase->update(['status' => 'Unpaid']);
                    }
                } elseif ($totalQuantityReturnedSoFar < $totalPurchasedQuantity) {
                    $purchase->update(['status' => 'Partial']);
                }
            }
        });
    }

    public function printReturn($id)
    {
        $por = PurchaseReturn::with(['purchase.supplier', 'items.product', 'user'])->findOrFail($id);
        $tenantId = app('currentTenant')->id ?? null;
        $shopname = User::where('tenant_id', $tenantId)->whereNotNull('shopname')->value('shopname');
        $address = User::where('tenant_id', $tenantId)->whereNotNull('address')->value('address');

        $html = view('admin.por.print-pdf', compact('por', 'shopname', 'address'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('purchase-return-'.$por->id.'.pdf', ['Attachment' => false]);
    }
}
