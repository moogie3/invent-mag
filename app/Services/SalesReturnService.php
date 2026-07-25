<?php

namespace App\Services;

use App\Models\SalesReturn;
use Illuminate\Http\Request;
use App\Models\Sales;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SalesReturnItem;
use App\Models\Product;
use App\Models\Account;
use Dompdf\Dompdf;
use App\Helpers\CurrencyHelper;
use App\Helpers\SalesHelper;
use App\Models\JournalEntry;
use App\Models\ProductWarehouse;
use App\Models\POItem;
use App\Models\CustomerCredit;
use App\Services\SalesService;

class SalesReturnService
{
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
            if ($quantity <= 0) break;
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
            if ($quantity <= 0) break;
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

            $returnedItems = array_filter($items, function($item) {
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
            $totalQuantityReturnedSoFar = $sale->salesReturns()->with('items')->get()->flatMap(function ($sr) {
                return $sr->items;
            })->sum('quantity');

            // Update sale status based on return quantity
            // Only change status for Unpaid invoices
            // For Paid invoices, keep as Paid (return creates credit, not refund)
            // Note: We check $wasPaid (actual payment status) not stored status
            if ($totalQuantityReturnedSoFar >= $totalOriginalQuantitySold) {
                // Only change to Returned if was NOT paid
                if (!$wasPaid) {
                    $sale->update(['status' => 'Returned']);
                }
            } elseif ($totalQuantityReturnedSoFar > 0) {
                // Only change to Partial if was NOT paid
                if (!$wasPaid) {
                    $sale->update(['status' => 'Partial']);
                }
            }

            // Copy tax data from the original sale.
            // Compute whenever rate > 0 — tax_id may be null for legacy records.
            $taxRate   = (float) ($sale->tax_rate ?? 0);
            $taxType   = $sale->tax_type ?? 'none';
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
                'user_id'  => Auth::id(),
            ]);

            // Create return items - each item tracks which sales line item it came from
            // via sales_item_id. This maintains the exact price and quantity context.
            foreach ($returnedItems as $itemData) {
                $salesItemId = $itemData['sales_item_id'] ?? null;
                $price = $itemData['price'] ?? 0;
                $returnedQuantity = $itemData['returned_quantity'];

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'sales_item_id'   => $salesItemId,
                    'product_id'      => $itemData['product_id'],
                    'quantity'        => $returnedQuantity,
                    'price'           => $price,
                    'total'           => $price * $returnedQuantity,
                ]);

                $product = Product::find($itemData['product_id']);
                if ($product) {
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

            $accountingSettings = app('currentTenant')->accounting_settings ?? [];
            if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['accounts_receivable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts receivable are not configured.');
            }

            $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id'] ?? null);
            $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id'] ?? null);
            $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id'] ?? null);
            $inventoryAccount = Account::find($accountingSettings['inventory_account_id'] ?? null);

            if (!$salesRevenueAccount || !$accountsReceivableAccount || !$costOfGoodsSoldAccount || !$inventoryAccount) {
                throw new \Exception('Required accounts (Sales Revenue, AR, COGS, Inventory) not found for Sales Return. Please check Accounting Settings.');
            }

            // Get original journal entry from the sale to calculate proportional COGS
            $originalJournalEntries = JournalEntry::where('sourceable_type', Sales::class)
                ->where('sourceable_id', $sale->id)
                ->where('status', 'posted')
                ->get();

            $originalSalesRevenueAmount = 0;
            $originalCOGSAmount = 0;

            foreach ($originalJournalEntries as $journalEntry) {
                foreach ($journalEntry->transactions as $transaction) {
                    if ($transaction->account_name === $salesRevenueAccount->name && $transaction->type === 'credit') {
                        $originalSalesRevenueAmount = $transaction->amount;
                    }
                    if ($transaction->account_name === $costOfGoodsSoldAccount->name && $transaction->type === 'debit') {
                        $originalCOGSAmount = $transaction->amount;
                    }
                }
            }

            // Calculate proportional amounts based on return ratio
            $returnRatio = $totalAmount > 0 && $originalSalesRevenueAmount > 0 ? $totalAmount / $originalSalesRevenueAmount : 0;
            $proportionalCOGS = $originalCOGSAmount * $returnRatio;

            // Create comprehensive journal entry for sales return
            // This reverses: AR, Sales Revenue, COGS, and PPN Keluaran (if applicable)
            $transactions = [
                // Reverse Accounts Receivable (customer no longer owes this much)
                ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $totalAmount],
                // Reverse Sales Revenue (reduce income)
                ['account_name' => $salesRevenueAccount->name, 'type' => 'debit', 'amount' => $totalAmount],
                // Reverse COGS (reduce expense)
                ['account_name' => $costOfGoodsSoldAccount->name, 'type' => 'credit', 'amount' => $proportionalCOGS],
                // Add inventory back (items returned to stock)
                ['account_name' => $inventoryAccount->name, 'type' => 'debit', 'amount' => $proportionalCOGS],
            ];

            // PPN reversal: if the original sale had PPN enabled and a Faktur Pajak,
            // reverse the PPN Keluaran journal entry proportionally
            if ($sale->isPpn() && $sale->ppn_amount > 0 && $sale->fakturPajak) {
                $ppnKeluaranAccountId = $accountingSettings['ppn_keluaran_account_id'] ?? null;
                if ($ppnKeluaranAccountId) {
                    $ppnKeluaranAccount = Account::find($ppnKeluaranAccountId);
                    if ($ppnKeluaranAccount) {
                        $returnRatio = $totalAmount > 0 && $sale->total > 0 ? $totalAmount / $sale->total : 0;
                        $proportionalPpn = round($sale->ppn_amount * $returnRatio, 2);

                        if ($proportionalPpn > 0) {
                            // Dr. PPN Keluaran (reduce VAT liability)
                            $transactions[] = ['account_name' => $ppnKeluaranAccount->name, 'type' => 'debit', 'amount' => $proportionalPpn];
                            // Cr. AR (reduce what customer owes for PPN)
                            $transactions[] = ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $proportionalPpn];
                        }
                    }
                }
            }

            $this->accountingService->createJournalEntry(
                "Sales Return for SO #{$sale->invoice}",
                Carbon::parse($salesReturn->return_date),
                $transactions,
                $salesReturn
            );

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
                    'reason' => 'Sales Return for Invoice #' . $sale->invoice,
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
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            $sale = $salesReturn->sale;

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
            $salesReturn->items()->delete();

            $returnedItems = array_filter($items, function($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['price'] ?? 0) * $itemData['returned_quantity'];
            }

            // Recalculate tax for updated return amount.
            // Compute whenever rate > 0 — tax_id may be null for legacy records.
            $sale->load('tax');
            $updatedTaxRate   = (float) ($sale->tax_rate ?? 0);
            $updatedTaxType   = $sale->tax_type ?? 'none';
            $updatedTaxAmount = $updatedTaxRate > 0
                ? SalesHelper::calculateTaxAmount($totalReturnAmount, $updatedTaxRate, $updatedTaxType)
                : 0.0;

            $salesReturn->update([
                'return_date'  => $data['return_date'],
                'reason'       => $data['reason'],
                'total_amount' => $totalReturnAmount,
                'tax_amount'   => $updatedTaxAmount,
                'tax_rate'     => $updatedTaxRate,
                'tax_type'     => $updatedTaxType,
                'status'       => $data['status'],
                'user_id'      => Auth::id(),
            ]);

            // Update return items - each item tracks which sales line item it came from
            // via sales_item_id. This maintains the exact price and quantity context.
            foreach ($returnedItems as $itemData) {
                $salesItemId = $itemData['sales_item_id'] ?? null;
                $price = $itemData['price'] ?? 0;
                $returnedQuantity = $itemData['returned_quantity'];

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'sales_item_id'   => $salesItemId,
                    'product_id'      => $itemData['product_id'],
                    'quantity'        => $returnedQuantity,
                    'price'           => $price,
                    'total'           => $price * $returnedQuantity,
                ]);

                $product = Product::find($itemData['product_id']);
                if ($product) {
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
            $totalQuantityReturnedSoFar = $sale->salesReturns->flatMap(function ($sr) {
                return $sr->items;
            })->sum('quantity');

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
                if (!$wasPaid) {
                    $sale->update(['status' => 'Returned']);
                }
            } elseif ($totalQuantityReturnedSoFar > 0) {
                // Only change to Partial if was NOT paid
                if (!$wasPaid) {
                    $sale->update(['status' => 'Partial']);
                }
            }

            // Handle customer credit for update - check if status changed to Completed and sale was originally Paid
            // Use actual payment status (wasPaid) instead of stored status
            $oldStatus = $salesReturn->getOriginal('status');
            $oldTotalAmount = $salesReturn->getOriginal('total_amount');
            
            if ($data['status'] === 'Completed' && $wasPaid && !$salesReturn->customer_credit_id) {
                $credit = CustomerCredit::create([
                    'tenant_id' => $sale->tenant_id,
                    'customer_id' => $sale->customer_id,
                    'sales_return_id' => $salesReturn->id,
                    'amount' => $totalReturnAmount,
                    'used_amount' => 0,
                    'available_amount' => $totalReturnAmount,
                    'reason' => 'Sales Return for Invoice #' . $sale->invoice,
                    'status' => 'active',
                ]);

                $salesReturn->update(['customer_credit_id' => $credit->id]);
            } elseif ($salesReturn->customer_credit_id && $totalReturnAmount != $oldTotalAmount) {
                // Update credit amount if it already exists
                $credit = CustomerCredit::find($salesReturn->customer_credit_id);
                if ($credit) {
                    $credit->update([
                        'amount' => $totalReturnAmount,
                        'available_amount' => $totalReturnAmount - $credit->used_amount
                    ]);
                }
            }

            // Recalculate sale status using SalesService to ensure consistency
            // This ensures the status reflects actual payments vs total
            // Load payments first to ensure accurate calculation
            $sale->load('payments');
            $this->salesService->updateSaleStatus($sale);

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
            $dompdf = new Dompdf();
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
            // Reverse associated journal entries for accurate accounting
            $journalEntries = JournalEntry::where('sourceable_type', get_class($salesReturn))
                ->where('sourceable_id', $salesReturn->id)
                ->get();

            foreach ($journalEntries as $entry) {
                if ($entry->status === JournalEntry::STATUS_POSTED && $entry->canBeReversed()) {
                    $this->accountingService->reverseJournalEntry($entry, "Auto-reversal due to deleted Sales Return #{$salesReturn->id}");
                }
            }

            $sale = $salesReturn->sale;
            if ($sale) {
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

            $salesReturn->delete();

            if ($sale) {
                $totalSoldQuantity = $sale->salesItems()->sum('quantity');
                $totalQuantityReturnedSoFar = $sale->salesReturns()->with('items')->get()->flatMap(function ($sr) {
                    return $sr->items;
                })->sum('quantity');

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

    public function bulkCompleteSalesReturns(array $ids): void
    {
        $salesReturns = SalesReturn::whereIn('id', $ids)->with(['sale.salesItems', 'sale.payments', 'items.salesItem'])->get();
        foreach ($salesReturns as $salesReturn) {
            $this->completeSalesReturn($salesReturn);
        }
    }

    public function completeSalesReturn(SalesReturn $salesReturn): void
    {
        if ($salesReturn->status === 'Completed') {
            return;
        }

        DB::transaction(function () use ($salesReturn) {
            $sale = $salesReturn->sale;
            $sale->load('payments');
            $wasPaid = $sale->total_paid >= $sale->grand_total;

            // Stock was already incremented in createSalesReturn() when the return
            // was first created (Pending). completeSalesReturn() only needs to post
            // the journal entry and issue the customer credit — NOT touch stock again.

            $this->createSalesReturnJournalEntry($salesReturn, $sale);

            if ($wasPaid) {
                $credit = CustomerCredit::create([
                    'tenant_id' => $sale->tenant_id,
                    'customer_id' => $sale->customer_id,
                    'sales_return_id' => $salesReturn->id,
                    'amount' => $salesReturn->total_amount,
                    'used_amount' => 0,
                    'available_amount' => $salesReturn->total_amount,
                    'reason' => 'Sales Return for Invoice #' . $sale->invoice,
                    'status' => 'active',
                ]);
                $salesReturn->update(['customer_credit_id' => $credit->id]);
            }

            $salesReturn->update(['status' => 'Completed']);

            $sale->load('payments');
            $this->salesService->updateSaleStatus($sale);
        });
    }

    private function createSalesReturnJournalEntry(SalesReturn $salesReturn, Sales $sale): void
    {
        $accountingSettings = app('currentTenant')->accounting_settings ?? [];
        if (empty($accountingSettings) || !isset($accountingSettings['accounts_receivable_account_id'])) {
            return;
        }

        $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id'] ?? null);
        $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id']);
        $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id'] ?? null);
        $inventoryAccount = Account::find($accountingSettings['inventory_account_id'] ?? null);

        if (!$salesRevenueAccount || !$accountsReceivableAccount || !$costOfGoodsSoldAccount || !$inventoryAccount) {
            return;
        }

        $totalAmount = $salesReturn->total_amount;

        $originalJournalEntries = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('status', 'posted')
            ->get();

        $originalSalesRevenueAmount = 0;
        $originalCOGSAmount = 0;

        foreach ($originalJournalEntries as $journalEntry) {
            foreach ($journalEntry->transactions as $transaction) {
                if ($transaction->account_name === $salesRevenueAccount->name && $transaction->type === 'credit') {
                    $originalSalesRevenueAmount = $transaction->amount;
                }
                if ($transaction->account_name === $costOfGoodsSoldAccount->name && $transaction->type === 'debit') {
                    $originalCOGSAmount = $transaction->amount;
                }
            }
        }

        $returnRatio = $totalAmount > 0 && $originalSalesRevenueAmount > 0 ? $totalAmount / $originalSalesRevenueAmount : 0;
        $proportionalCOGS = $originalCOGSAmount * $returnRatio;

        $transactions = [
            ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $totalAmount],
            ['account_name' => $salesRevenueAccount->name, 'type' => 'debit', 'amount' => $totalAmount],
            ['account_name' => $costOfGoodsSoldAccount->name, 'type' => 'credit', 'amount' => $proportionalCOGS],
            ['account_name' => $inventoryAccount->name, 'type' => 'debit', 'amount' => $proportionalCOGS],
        ];

        // PPN reversal: if the original sale had PPN enabled and a Faktur Pajak,
        // reverse the PPN Keluaran journal entry proportionally
        if ($sale->isPpn() && $sale->ppn_amount > 0 && $sale->fakturPajak) {
            $ppnKeluaranAccountId = $accountingSettings['ppn_keluaran_account_id'] ?? null;
            if ($ppnKeluaranAccountId) {
                $ppnKeluaranAccount = Account::find($ppnKeluaranAccountId);
                if ($ppnKeluaranAccount) {
                    $returnRatio = $totalAmount > 0 && $sale->total > 0 ? $totalAmount / $sale->total : 0;
                    $proportionalPpn = round($sale->ppn_amount * $returnRatio, 2);

                    if ($proportionalPpn > 0) {
                        $transactions[] = ['account_name' => $ppnKeluaranAccount->name, 'type' => 'debit', 'amount' => $proportionalPpn];
                        $transactions[] = ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $proportionalPpn];
                    }
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
            if ($salesReturn->status === 'Completed') {
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

                $journalEntries = JournalEntry::where('sourceable_type', get_class($salesReturn))
                    ->where('sourceable_id', $salesReturn->id)
                    ->get();
                foreach ($journalEntries as $entry) {
                    if ($entry->status === JournalEntry::STATUS_POSTED && $entry->canBeReversed()) {
                        $this->accountingService->reverseJournalEntry($entry, "Reversal due to canceled Sales Return #{$salesReturn->id}");
                    }
                }

                if ($salesReturn->customer_credit_id) {
                    $credit = $salesReturn->credit;
                    if ($credit) {
                        $credit->update(['status' => 'canceled', 'available_amount' => 0]);
                    }
                }
            }

            $salesReturn->update(['status' => 'Canceled']);

            $sale = $salesReturn->sale;
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
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('sales-return-' . $salesReturn->id . '.pdf', ['Attachment' => false]);
    }
}