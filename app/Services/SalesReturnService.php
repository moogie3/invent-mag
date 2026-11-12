<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Helpers\ReturnTaxHelper;
use App\Helpers\SalesHelper;
use App\Models\Account;
use App\Models\CustomerCredit;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use App\Services\Concerns\BuildsListSearch;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    use BuildsListSearch;

    protected AccountingService $accountingService;

    protected SalesService $salesService;

    public function __construct(AccountingService $accountingService, SalesService $salesService)
    {
        $this->accountingService = $accountingService;
        $this->salesService = $salesService;
    }

    private function decrementPOItemRemaining($productId, $quantity)
    {
        $poItems = POItem::where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
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

    private function incrementPOItemRemaining($productId, $quantity)
    {
        $poItems = POItem::where('product_id', $productId)
            ->whereColumn('remaining_quantity', '<', 'quantity')
            ->orderByDesc('expiry_date')
            ->orderByDesc('created_at')
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

    public function getSalesReturnIndexData(array $filters, int $entries)
    {
        $query = SalesReturn::with(['sale', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('return_date', $filters['month']);
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('return_date', $filters['year']);
        }

        // Whole-catalog search across the return itself, the originating
        // invoice and that invoice's customer.
        $this->applyListSearch($query, $filters, ['sales_returns.reason', 'sales_returns.status'], [
            'sale' => ['sales.invoice'],
            'sale.customer' => ['customers.name'],
        ]);

        $returns = $query->paginate($entries);

        $statusCounts = SalesReturn::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'returns' => $returns,
            'total_returns' => $returns->total(),
            'total_amount' => SalesReturn::sum('total_amount'),
            'completed_count' => $statusCounts->get('Completed', 0),
            'pending_count' => $statusCounts->get('Pending', 0),
            'canceled_count' => $statusCounts->get('Canceled', 0),
            'entries' => $entries,
        ];
    }

    public function createSalesReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $items = json_decode($data['items'], true);

            if (empty($items)) {
                throw new \Exception('No items selected for return.');
            }

            $returnedItems = array_filter($items, function ($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            if (empty($returnedItems)) {
                throw new \Exception('No items selected for return.');
            }

            $totalAmount = 0;
            foreach ($returnedItems as $item) {
                $totalAmount += ($item['price'] ?? 0) * $item['returned_quantity'];
            }

            // Get original sale status BEFORE creating return and modifying it
            $sale = Sales::find($data['sales_id']);
            $sale->load('payments');
            $originalSaleStatus = $sale->status;
            $wasPaid = $sale->total_paid >= $sale->grand_total;

            // Calculate return quantities
            $totalOriginalQuantitySold = $sale->salesItems()->sum('quantity');
            // Only LIVE returns count: a canceled return has already given its
            // stock and its journal entry back, so it cannot keep marking the
            // sale Partial/Returned (cancel keeps the row for history).
            $totalQuantityReturnedSoFar = $sale->salesReturns()
                ->where('status', '!=', 'Canceled')
                ->with('items')->get()->flatMap(function ($sr) {
                    return $sr->items;
                })->sum('quantity');

            // Update sale status based on return quantity
            // Only change status for Unpaid invoices
            // For Paid invoices, keep as Paid (return creates credit, not refund)
            // Note: We check $wasPaid (actual payment status) not stored status
            if ($totalQuantityReturnedSoFar >= $totalOriginalQuantitySold) {
                // Only change to Returned if was NOT paid
                if (! $wasPaid) {
                    $sale->update(['status' => 'Returned']);
                }
            } elseif ($totalQuantityReturnedSoFar > 0) {
                // Only change to Partial if was NOT paid
                if (! $wasPaid) {
                    $sale->update(['status' => 'Partial']);
                }
            }

            // Copy tax data from the original sale.
            // Compute whenever rate > 0 — tax_id may be null for legacy records.
            $taxRate = (float) ($sale->tax_rate ?? 0);
            $taxType = $sale->tax_type ?? 'none';
            $taxAmount = $taxRate > 0
                ? SalesHelper::calculateTaxAmount($totalAmount, $taxRate, $taxType)
                : 0.0;

            $salesReturn = SalesReturn::create([
                'sales_id' => $data['sales_id'],
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'status' => $data['status'],
                'total_amount' => $totalAmount,
                'tax_id' => $sale->tax_id,
                'tax_rate' => $sale->tax_rate ?? 0,
                'tax_amount' => $taxAmount,
                'tax_type' => $sale->tax_type ?? 'none',
                'user_id' => Auth::id(),
            ]);

            // A return born Canceled holds no stock and no live journal entry:
            // cancel gives both back, so creating one with them would leave the
            // row in the state cancel exists to prevent. The form no longer
            // offers Canceled; this backstop covers seeders, factories and any
            // caller that bypasses it.
            $isCanceled = $data['status'] === 'Canceled';

            // Create return items - each item tracks which sales line item it came from
            // via sales_item_id. This maintains the exact price and quantity context.
            foreach ($returnedItems as $itemData) {
                $salesItemId = $itemData['sales_item_id'] ?? null;
                $price = $itemData['price'] ?? 0;
                $returnedQuantity = $itemData['returned_quantity'];

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'sales_item_id' => $salesItemId,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $price,
                    'total' => $price * $returnedQuantity,
                ]);

                // A Canceled return moves no stock, so the move cancel would
                // have to undo is never made in the first place.
                $product = Product::find($itemData['product_id']);
                if ($product && ! $isCanceled) {
                    $sale = Sales::find($data['sales_id']);
                    $warehouseId = $sale->warehouse_id ?? Product::getMainWarehouseId();

                    $stockRecord = ProductWarehouse::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $warehouseId, 'tenant_id' => $product->tenant_id],
                        ['quantity' => 0]
                    );
                    $stockRecord->increment('quantity', $returnedQuantity);

                    // Restore expiry tracking (POItem remaining_quantity)
                    $this->incrementPOItemRemaining($product->id, $returnedQuantity);
                }
            }

            $sale = Sales::find($data['sales_id']);
            // Note: Sale status update moved to BEFORE return creation (see above)
            // This duplicate code is kept for backward compatibility but should be cleaned up later

            // The journal block itself lives in createSalesReturnJournalEntry(),
            // shared with the edit path. strict: true settles the two callers'
            // contract: creation throws and rolls the whole return back when the
            // GL cannot be written (a return born without its entry would hold
            // stock with no booking), while an edit degrades instead, because the
            // entry it replaces already exists. A Canceled return posts nothing:
            // it holds no stock either, so there is nothing for an entry to
            // describe.
            if (! $isCanceled) {
                $this->createSalesReturnJournalEntry($salesReturn, $sale, strict: true);
            }

            // Create customer credit if return is completed and original sale was paid
            // Use actual payment status (wasPaid) instead of stored status
            if ($data['status'] === 'Completed' && $wasPaid) {
                $credit = CustomerCredit::create([
                    'tenant_id' => $sale->tenant_id,
                    'customer_id' => $sale->customer_id,
                    'sales_return_id' => $salesReturn->id,
                    'amount' => $totalAmount,
                    'used_amount' => 0,
                    'available_amount' => $totalAmount,
                    'reason' => 'Sales Return for Invoice #'.$sale->invoice,
                    'status' => 'active',
                ]);

                $salesReturn->update(['customer_credit_id' => $credit->id]);
            }

            // Recalculate sale status using SalesService to ensure consistency
            $sale->load('payments');
            $this->salesService->updateSaleStatus($sale);

            return $salesReturn;
        });
    }

    public function updateSalesReturn(SalesReturn $salesReturn, array $data): SalesReturn
    {
        return DB::transaction(function () use ($salesReturn, $data) {
            $items = json_decode($data['items'], true);
            if (! $items || ! is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            $sale = $salesReturn->sale;

            // Both sides of the status change, captured before anything is
            // written: stock may only move for a return that HELD stock (was
            // live) into one that will HOLD stock (stays live). A Canceled
            // return holds nothing, so each half is skipped accordingly.
            $wasCanceled = $salesReturn->status === 'Canceled';
            $isCanceled = $data['status'] === 'Canceled';

            // A Canceled return holds no stock, so there is nothing to hand
            // back when the edit touches one: reverting its old items would
            // take goods out of the warehouse the return never put in.
            if (! $wasCanceled) {
                foreach ($salesReturn->items as $oldItem) {
                    $product = Product::find($oldItem->product_id);
                    if ($product) {
                        $warehouseId = $sale->warehouse_id ?? Product::getMainWarehouseId();

                        $stockRecord = ProductWarehouse::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();
                        if ($stockRecord) {
                            $stockRecord->decrement('quantity', $oldItem->quantity);
                        }
                        $this->decrementPOItemRemaining($product->id, $oldItem->quantity);
                    }
                }
            }
            $salesReturn->items()->delete();

            $returnedItems = array_filter($items, function ($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['price'] ?? 0) * $itemData['returned_quantity'];
            }

            // Recalculate tax for updated return amount.
            // Compute whenever rate > 0 — tax_id may be null for legacy records.
            $sale->load('tax');
            $updatedTaxRate = (float) ($sale->tax_rate ?? 0);
            $updatedTaxType = $sale->tax_type ?? 'none';
            $updatedTaxAmount = $updatedTaxRate > 0
                ? SalesHelper::calculateTaxAmount($totalReturnAmount, $updatedTaxRate, $updatedTaxType)
                : 0.0;

            $salesReturn->update([
                'return_date' => $data['return_date'],
                // ?? null like createSalesReturn() and both purchase-return
                // paths: 'reason' is not in the controller's required rules,
                // so a form that omits it reached here bare and 500'd on an
                // undefined key while every sibling path wrote null.
                'reason' => $data['reason'] ?? null,
                'total_amount' => $totalReturnAmount,
                'tax_amount' => $updatedTaxAmount,
                'tax_rate' => $updatedTaxRate,
                'tax_type' => $updatedTaxType,
                'status' => $data['status'],
                'user_id' => Auth::id(),
            ]);

            // Update return items - each item tracks which sales line item it came from
            // via sales_item_id. This maintains the exact price and quantity context.
            foreach ($returnedItems as $itemData) {
                $salesItemId = $itemData['sales_item_id'] ?? null;
                $price = $itemData['price'] ?? 0;
                $returnedQuantity = $itemData['returned_quantity'];

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'sales_item_id' => $salesItemId,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $price,
                    'total' => $price * $returnedQuantity,
                ]);

                $product = Product::find($itemData['product_id']);
                // A return edited to Canceled must not keep (or start) holding
                // stock: the item rows are still written for history, only the
                // stock move is skipped - the same guard createSalesReturn()
                // applies to a return born Canceled.
                if ($product && ! $isCanceled) {
                    $warehouseId = $sale->warehouse_id ?? Product::getMainWarehouseId();

                    $stockRecord = ProductWarehouse::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $warehouseId, 'tenant_id' => $product->tenant_id],
                        ['quantity' => 0]
                    );
                    $stockRecord->increment('quantity', $returnedQuantity);

                    // Restore expiry tracking (POItem remaining_quantity)
                    $this->incrementPOItemRemaining($product->id, $returnedQuantity);
                }
            }

            $totalOriginalQuantitySold = $sale->salesItems()->sum('quantity');
            // Only LIVE returns count: a canceled row keeps its items for
            // history but has given its stock back, so it cannot keep the
            // sale Partial/Returned - the same rule create, cancel and delete
            // apply. (The payments-based normalize at the end overwrites this
            // today; the filter keeps the recount honest on its own.)
            $totalQuantityReturnedSoFar = $sale->salesReturns()
                ->where('status', '!=', 'Canceled')
                ->with('items')
                ->get()
                ->flatMap(function ($sr) {
                    return $sr->items;
                })
                ->sum('quantity');

            // Capture original sale status BEFORE any modifications
            // Important: Load payments first to determine if sale was truly paid
            $sale->load('payments');
            $originalSaleStatus = $sale->status;
            $wasPaid = $sale->total_paid >= $sale->grand_total;

            // Update sale status only for Unpaid invoices
            // For Paid invoices, keep as Paid (return creates credit, not refund)
            // Note: We check $wasPaid (actual payment status) not stored status
            if ($totalQuantityReturnedSoFar >= $totalOriginalQuantitySold) {
                // Only change to Returned if was NOT paid
                if (! $wasPaid) {
                    $sale->update(['status' => 'Returned']);
                }
            } elseif ($totalQuantityReturnedSoFar > 0) {
                // Only change to Partial if was NOT paid
                if (! $wasPaid) {
                    $sale->update(['status' => 'Partial']);
                }
            }

            // The customer credit lives and dies with the return's status:
            // active only while the return is Completed, canceled the moment
            // it is not (same write cancelSalesReturn() makes). Creation still
            // requires the sale to have been actually paid. An existing credit
            // is reactivated when the return comes back to Completed instead
            // of being left dead, so a reopened return cannot hold a canceled
            // credit, and its amount is re-synced here - the old
            // getOriginal('total_amount') comparison ran AFTER the update
            // above, so it always saw the new total and never fired.
            $credit = $salesReturn->customer_credit_id
                ? CustomerCredit::find($salesReturn->customer_credit_id)
                : null;

            if ($data['status'] === 'Completed') {
                if ($credit) {
                    $credit->update([
                        'amount' => $totalReturnAmount,
                        'available_amount' => max(0, $totalReturnAmount - $credit->used_amount),
                        'status' => 'active',
                    ]);
                } elseif ($wasPaid) {
                    $credit = CustomerCredit::create([
                        'tenant_id' => $sale->tenant_id,
                        'customer_id' => $sale->customer_id,
                        'sales_return_id' => $salesReturn->id,
                        'amount' => $totalReturnAmount,
                        'used_amount' => 0,
                        'available_amount' => $totalReturnAmount,
                        'reason' => 'Sales Return for Invoice #'.$sale->invoice,
                        'status' => 'active',
                    ]);

                    $salesReturn->update(['customer_credit_id' => $credit->id]);
                }
            } elseif ($credit) {
                $credit->update(['status' => 'canceled', 'available_amount' => 0]);
            }

            // Recalculate sale status using SalesService to ensure consistency
            // This ensures the status reflects actual payments vs total
            // Load payments first to ensure accurate calculation
            $sale->load('payments');
            $this->salesService->updateSaleStatus($sale);

            // Re-journal so the GL reflects the edit: reverse whatever the
            // previous version posted (entries a prior reversal already netted
            // out are skipped), then post the updated entry with the new
            // totals. A canceled return keeps the reversal only, and a
            // zeroed-out return nets to zero instead of posting an empty
            // entry - the contract updatePurchaseReturn() already honours, so
            // editing a return cannot leave the GL describing the old one.
            $this->reverseSalesReturnJournalEntries(
                $salesReturn,
                "Reversal due to edited Sales Return #{$salesReturn->id}"
            );

            if ($salesReturn->status !== 'Canceled' && (float) $salesReturn->total_amount > 0) {
                $this->createSalesReturnJournalEntry($salesReturn, $sale);
            }

            return $salesReturn->fresh();
        });
    }

    public function bulkExportSalesReturns(array $filters, ?array $ids, string $exportOption)
    {
        $query = SalesReturn::with(['sale', 'user']);

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

        $salesReturns = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.sales-returns.bulk-export-pdf', compact('salesReturns'))->render();
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->stream('sales-returns.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=sales-returns.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($salesReturns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Sales Invoice',
                    'Return Date',
                    'Total Amount',
                    'Status',
                    'Returned By',
                ]);

                foreach ($salesReturns as $sr) {
                    fputcsv($file, [
                        $sr->sale->invoice,
                        Carbon::parse($sr->return_date)->format('Y-m-d'),
                        CurrencyHelper::format($sr->total_amount),
                        $sr->status,
                        $sr->user->name,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function deleteSalesReturn(SalesReturn $salesReturn): void
    {
        DB::transaction(function () use ($salesReturn) {
            // Reverse associated journal entries for accurate accounting -
            // through the shared guard, so an entry an edit already reversed
            // is not reversed a second time on the way out.
            $this->reverseSalesReturnJournalEntries(
                $salesReturn,
                "Auto-reversal due to deleted Sales Return #{$salesReturn->id}"
            );

            // A Canceled return already gave its stock back on the way to
            // Canceled, so delete only removes the row: reverting here moved
            // the same goods a second time and drove inventory below what the
            // sale ever held. The journal reversal above stays unconditional
            // because its shared guard makes it a no-op for an entry cancel
            // already netted out.
            $isCanceled = $salesReturn->status === 'Canceled';

            $sale = $salesReturn->sale;
            if ($sale && ! $isCanceled) {
                foreach ($salesReturn->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $warehouseId = $sale->warehouse_id ?? Product::getMainWarehouseId();

                        $stockRecord = ProductWarehouse::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouseId)
                            ->where('tenant_id', $product->tenant_id)
                            ->first();

                        if ($stockRecord) {
                            $stockRecord->decrement('quantity', $item->quantity);
                        }
                        $this->decrementPOItemRemaining($product->id, $item->quantity);
                    }
                }
            }

            // Mirror of cancelSalesReturn(): a return's credit dies with it.
            // The foreign key is nullOnDelete, so without this the credit
            // outlived the return as a live balance against a row that no
            // longer exists. Idempotent for a return cancel already handled.
            if ($salesReturn->customer_credit_id) {
                $credit = $salesReturn->credit;
                if ($credit) {
                    $credit->update(['status' => 'canceled', 'available_amount' => 0]);
                }
            }

            $salesReturn->delete();

            if ($sale) {
                $totalSoldQuantity = $sale->salesItems()->sum('quantity');
                // Only LIVE returns count, same as create and cancel: a
                // canceled row keeps its items for history but has given its
                // stock back, so counting it here would leave the sale on
                // Partial with nothing actually returned.
                $totalQuantityReturnedSoFar = $sale->salesReturns()
                    ->where('status', '!=', 'Canceled')
                    ->with('items')
                    ->get()
                    ->flatMap(function ($sr) {
                        return $sr->items;
                    })
                    ->sum('quantity');

                if ($totalQuantityReturnedSoFar <= 0) {
                    if ($sale->total_paid >= $sale->total) {
                        $sale->update(['status' => 'Paid']);
                    } elseif ($sale->total_paid > 0) {
                        $sale->update(['status' => 'Partial']);
                    } else {
                        $sale->update(['status' => 'Unpaid']);
                    }
                } elseif ($totalQuantityReturnedSoFar < $totalSoldQuantity) {
                    $sale->update(['status' => 'Partial']);
                }
            }
        });
    }

    public function bulkDeleteSalesReturns(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $salesReturns = SalesReturn::whereIn('id', $ids)->with('items')->get();
            foreach ($salesReturns as $salesReturn) {
                $this->deleteSalesReturn($salesReturn);
            }
        });
    }

    /**
     * Mark the selected returns Completed, one row at a time. A Canceled row
     * is skipped rather than failed: it already gave its stock back and
     * reversed its journal entry, so "completing" it would mint a customer
     * credit against a row that holds nothing. The counts come back so the
     * caller can tell the user exactly what happened instead of pretending
     * the whole batch went through.
     *
     * @param  array<int, int>  $ids
     * @return array{completed: int, already_completed: int, skipped: int, completed_ids: array<int, int>}
     */
    public function bulkCompleteSalesReturns(array $ids): array
    {
        $counts = ['completed' => 0, 'already_completed' => 0, 'skipped' => 0, 'completed_ids' => []];

        $salesReturns = SalesReturn::whereIn('id', $ids)->with(['sale.salesItems', 'sale.payments', 'items.salesItem'])->get();
        foreach ($salesReturns as $salesReturn) {
            if ($salesReturn->status === 'Canceled') {
                $counts['skipped']++;

                continue;
            }

            if ($salesReturn->status === 'Completed') {
                $counts['already_completed']++;

                continue;
            }

            $this->completeSalesReturn($salesReturn);
            $counts['completed']++;
            $counts['completed_ids'][] = $salesReturn->id;
        }

        return $counts;
    }

    public function completeSalesReturn(SalesReturn $salesReturn): void
    {
        if ($salesReturn->status === 'Completed') {
            return;
        }

        // A Canceled return holds no stock and no live journal entry, so
        // completing it would issue a customer credit against nothing. The
        // bulk action skips these rows; this throw is the server-side
        // backstop for anything that calls the service directly.
        if ($salesReturn->status === 'Canceled') {
            throw new \Exception('A canceled sales return cannot be completed.');
        }

        DB::transaction(function () use ($salesReturn) {
            $sale = $salesReturn->sale;
            $sale->load('payments');
            $wasPaid = $sale->total_paid >= $sale->grand_total;

            // Stock AND the journal entry were both posted by createSalesReturn()
            // when the return was first created (Pending): the stock movement and
            // its GL reversal are one event, and a Pending return has already
            // moved both - the same contract PurchaseReturnService honours, where
            // create books the entry and there is no separate "complete" step to
            // book it again. Posting here a second time booked the identical
            // entry twice, so every Pending -> Completed return doubled its GL
            // impact. What completion still owns is the credit: only now does the
            // money become available to the customer.

            if ($wasPaid) {
                $credit = CustomerCredit::create([
                    'tenant_id' => $sale->tenant_id,
                    'customer_id' => $sale->customer_id,
                    'sales_return_id' => $salesReturn->id,
                    'amount' => $salesReturn->total_amount,
                    'used_amount' => 0,
                    'available_amount' => $salesReturn->total_amount,
                    'reason' => 'Sales Return for Invoice #'.$sale->invoice,
                    'status' => 'active',
                ]);
                $salesReturn->update(['customer_credit_id' => $credit->id]);
            }

            $salesReturn->update(['status' => 'Completed']);

            $sale->load('payments');
            $this->salesService->updateSaleStatus($sale);
        });
    }

    /**
     * Reverse this return's posted journal entries. Entries a prior reversal
     * already netted out are skipped: reverseJournalEntry() leaves the
     * original status 'posted' (the link lives on the reversal's
     * reversed_entry_id), so without the guard a second edit - or an edit
     * followed by delete/cancel - would reverse the same entry twice and
     * corrupt the GL in the opposite direction. Mirrors
     * PurchaseReturnService::reversePurchaseReturnJournalEntries(), which
     * delete and cancel route through as well: one guard, three callers, so
     * an edit cannot leave the other two paths holding a weaker test.
     */
    private function reverseSalesReturnJournalEntries(SalesReturn $salesReturn, string $note): void
    {
        $journalEntries = JournalEntry::where('sourceable_type', get_class($salesReturn))
            ->where('sourceable_id', $salesReturn->id)
            ->get();

        foreach ($journalEntries as $entry) {
            if ($entry->canBeReversed() && ! $entry->hasDependentEntries()) {
                $this->accountingService->reverseJournalEntry($entry, $note);
            }
        }
    }

    /**
     * The proportional reversal a return against this sale will book.
     *
     * Single source of truth: createSalesReturnJournalEntry() and the
     * return screens both read this, so a figure shown to the user cannot
     * drift from the figure posted to the ledger. The base is the original
     * sale's revenue CREDIT (net of PPN), never sale->total (gross).
     *
     * @return array{ratio: float, ppn: float, pph: float, cogs: float}
     */
    public function deriveReversal(Sales $sale, float $returnTotal): array
    {
        $accountingSettings = app('currentTenant')->accounting_settings ?? [];

        $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id'] ?? null);
        $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id'] ?? null);

        // Without both accounts there is no base to prorate from: every
        // derived amount falls to 0, so nothing is booked and nothing is
        // shown.
        if (! $salesRevenueAccount || ! $costOfGoodsSoldAccount) {
            return ['ratio' => 0.0, 'ppn' => 0.0, 'pph' => 0.0, 'cogs' => 0.0];
        }

        // Transactions store account_id only, so resolve names through the
        // relation or the base amounts stay 0.
        $originalJournalEntries = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('status', 'posted')
            ->with('transactions.account')
            ->get();

        $originalSalesRevenueAmount = 0;
        $originalCOGSAmount = 0;

        foreach ($originalJournalEntries as $journalEntry) {
            foreach ($journalEntry->transactions as $transaction) {
                if ($transaction->account?->name === $salesRevenueAccount->name && $transaction->type === 'credit') {
                    $originalSalesRevenueAmount = $transaction->amount;
                }
                if ($transaction->account?->name === $costOfGoodsSoldAccount->name && $transaction->type === 'debit') {
                    $originalCOGSAmount = $transaction->amount;
                }
            }
        }

        $reversal = ReturnTaxHelper::salesReversal(
            $returnTotal,
            $originalSalesRevenueAmount,
            (float) $sale->ppn_amount,
            $sale->isPpn(),
            (float) $sale->pph_amount,
            $sale->isPph(),
        );

        $reversal['cogs'] = $originalCOGSAmount * $reversal['ratio'];

        return $reversal;
    }

    /**
     * Post the return's journal entry: reverse AR and revenue, net COGS and
     * inventory by the return ratio, and reverse PPN/PPh proportionally.
     *
     * $strict is the settled contract between the two callers. Creation passes
     * true: a return cannot be born holding stock with no booking, so missing
     * settings/accounts throw and the surrounding transaction rolls the whole
     * return back (the messages are the ones createSalesReturn() always threw).
     * The edit path leaves it false: the entry being replaced already exists,
     * so degrading there never leaves the GL worse than before.
     */
    private function createSalesReturnJournalEntry(SalesReturn $salesReturn, Sales $sale, bool $strict = false): void
    {
        $accountingSettings = app('currentTenant')->accounting_settings ?? [];
        if (! $accountingSettings || ! isset($accountingSettings['inventory_account_id']) || ! isset($accountingSettings['accounts_receivable_account_id'])) {
            if ($strict) {
                throw new \Exception('Accounting settings for inventory or accounts receivable are not configured.');
            }

            return;
        }

        $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id'] ?? null);
        $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id']);
        $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id'] ?? null);
        $inventoryAccount = Account::find($accountingSettings['inventory_account_id'] ?? null);

        if (! $salesRevenueAccount || ! $accountsReceivableAccount || ! $costOfGoodsSoldAccount || ! $inventoryAccount) {
            if ($strict) {
                throw new \Exception('Required accounts (Sales Revenue, AR, COGS, Inventory) not found for Sales Return. Please check Accounting Settings.');
            }

            return;
        }

        $totalAmount = $salesReturn->total_amount;

        // The scan, the ratio and both tax reversals come from one shared
        // derivation, so a figure shown on the return screens can never
        // drift from the figure booked here.
        $reversal = $this->deriveReversal($sale, $totalAmount);
        $proportionalCOGS = $reversal['cogs'];

        $transactions = [
            ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $totalAmount],
            ['account_name' => $salesRevenueAccount->name, 'type' => 'debit', 'amount' => $totalAmount],
            ['account_name' => $costOfGoodsSoldAccount->name, 'type' => 'credit', 'amount' => $proportionalCOGS],
            ['account_name' => $inventoryAccount->name, 'type' => 'debit', 'amount' => $proportionalCOGS],
        ];

        // PPN reversal: the original sale recognised output VAT at creation
        // (VAT split — Dr AR / Cr Revenue / Cr PPN Keluaran), so a return
        // reverses the PPN Keluaran proportionally regardless of whether a
        // Faktur Pajak has been issued for the sale.
        // Net-base ratio (the original revenue credit), not sale->total,
        // which is gross of PPN.
        if ($reversal['ppn'] > 0) {
            $ppnKeluaranAccountId = $accountingSettings['ppn_keluaran_account_id'] ?? null;
            if ($ppnKeluaranAccountId) {
                $ppnKeluaranAccount = Account::find($ppnKeluaranAccountId);
                if ($ppnKeluaranAccount) {
                    $transactions[] = ['account_name' => $ppnKeluaranAccount->name, 'type' => 'debit', 'amount' => $reversal['ppn']];
                    $transactions[] = ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $reversal['ppn']];
                }
            }
        }

        // Reverse PPh withholding proportionally (net-base ratio: the
        // original revenue credit, not sale->total).
        if ($reversal['pph'] > 0) {
            $pphReceivableAccountId = $accountingSettings['pph_receivable_account_id'] ?? null;
            if ($pphReceivableAccountId) {
                $pphReceivableAccount = Account::find($pphReceivableAccountId);
                if ($pphReceivableAccount) {
                    // Reverse original PPh: Dr AR / Cr PPh Receivable
                    $transactions[] = ['account_name' => $accountsReceivableAccount->name, 'type' => 'debit', 'amount' => $reversal['pph']];
                    $transactions[] = ['account_name' => $pphReceivableAccount->name, 'type' => 'credit', 'amount' => $reversal['pph']];
                }
            }
        }

        $this->accountingService->createJournalEntry(
            "Sales Return for SO #{$sale->invoice}",
            Carbon::parse($salesReturn->return_date),
            $transactions,
            $salesReturn
        );
    }

    public function bulkCancelSalesReturns(array $ids): void
    {
        $salesReturns = SalesReturn::with(['items.product', 'sale.payments'])->whereIn('id', $ids)->get();
        foreach ($salesReturns as $salesReturn) {
            $this->cancelSalesReturn($salesReturn);
        }
    }

    public function cancelSalesReturn(SalesReturn $salesReturn): void
    {
        if ($salesReturn->status === 'Canceled') {
            return;
        }

        DB::transaction(function () use ($salesReturn) {
            // Revert for EVERY live status, not just Completed.
            // createSalesReturn() moves the stock and posts the journal the
            // moment the return is born, whatever status it is born with, so a
            // return canceled from Pending is holding both and has to give them
            // back - the Completed-only gate leaked them and left a live GL
            // entry against a canceled return. deleteSalesReturn() already
            // reverts unconditionally; cancel is now the same contract (the
            // Canceled early-return above is the idempotency guard). The credit
            // half stays conditionally: only completion issues one, so a Pending
            // return has no customer_credit_id to cancel.
            $sale = $salesReturn->sale;
            $sale->load('payments');

            foreach ($salesReturn->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $warehouseId = $sale->warehouse_id ?? Product::getMainWarehouseId();
                    $stockRecord = ProductWarehouse::where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->first();
                    if ($stockRecord) {
                        $stockRecord->decrement('quantity', $item->quantity);
                    }
                    $this->decrementPOItemRemaining($product->id, $item->quantity);
                }
            }

            // Same shared guard as delete: only the live entry is reversed,
            // never one an edit already netted out.
            $this->reverseSalesReturnJournalEntries(
                $salesReturn,
                "Reversal due to canceled Sales Return #{$salesReturn->id}"
            );

            if ($salesReturn->customer_credit_id) {
                $credit = $salesReturn->credit;
                if ($credit) {
                    $credit->update(['status' => 'canceled', 'available_amount' => 0]);
                }
            }

            $salesReturn->update(['status' => 'Canceled']);

            $sale->load('payments');
            $this->salesService->updateSaleStatus($sale);
        });
    }

    public function printReturn($id)
    {
        $salesReturn = SalesReturn::with(['sale.customer', 'items.product', 'user'])->findOrFail($id);
        $tenantId = app('currentTenant')->id ?? null;
        $shopname = User::where('tenant_id', $tenantId)->whereNotNull('shopname')->value('shopname');
        $address = User::where('tenant_id', $tenantId)->whereNotNull('address')->value('address');

        $html = view('admin.sales-returns.print-pdf', compact('salesReturn', 'shopname', 'address'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('sales-return-'.$salesReturn->id.'.pdf', ['Attachment' => false]);
    }
}
