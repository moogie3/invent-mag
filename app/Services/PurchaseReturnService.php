<?php

namespace App\Services;

use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use App\Models\Purchase;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\PurchaseReturnItem;
use App\Models\Product;
use App\Models\Account;

class PurchaseReturnService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
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

        $returns = $query->paginate($entries);

        return [
            'returns' => $returns,
            'total_returns' => $returns->total(),
            'total_amount' => PurchaseReturn::sum('total_amount'),
        ];
    }

    public function updatePurchaseReturn(PurchaseReturn $purchaseReturn, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn, $data) {
            $purchase = Purchase::findOrFail($data['purchase_id']);
            $items = json_decode($data['items'], true);
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            // Revert old stock quantities
            foreach ($purchaseReturn->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $oldItem->quantity);
                }
            }

            $purchaseReturn->items()->delete();

            $totalReturnAmount = 0;
            foreach ($items as $itemData) {
                $totalReturnAmount += $itemData['price'] * $itemData['quantity'];
            }

            $purchaseReturn->update([
                'purchase_id' => $purchase->id,
                'user_id' => Auth::id(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
            ]);

            foreach ($items as $itemData) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'total' => $itemData['price'] * $itemData['quantity'],
                ]);

                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $product->decrement('stock_quantity', $itemData['quantity']);
                }
            }

            // Update original purchase status
            $totalPurchasedQuantity = $purchase->items->sum('quantity');
            $totalReturnedQuantity = $purchase->purchaseReturns->flatMap->items->sum('quantity');

            if ($totalReturnedQuantity >= $totalPurchasedQuantity) {
                $purchase->update(['status' => 'Returned']);
            } else {
                $purchase->update(['status' => 'Partial']);
            }

            // Create Journal Entry for the return
            $accountingSettings = Auth::user()->accounting_settings;
            if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['accounts_payable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts payable are not configured.');
            }

            $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
            $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;

            $transactions = [
                ['account_name' => $accountsPayableAccountName, 'type' => 'debit', 'amount' => $totalReturnAmount],
                ['account_name' => $inventoryAccountName, 'type' => 'credit', 'amount' => $totalReturnAmount],
            ];

            $this->accountingService->createJournalEntry(
                "Purchase Return for PO #{$purchase->invoice}",
                Carbon::parse($data['return_date']),
                $transactions,
                $purchaseReturn
            );

            return $purchaseReturn;
        });
    }
}
