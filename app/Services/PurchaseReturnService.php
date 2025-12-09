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
            $items = json_decode($data['items'], true);
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            // Revert old stock quantities before deleting
            foreach ($purchaseReturn->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $oldItem->quantity);
                }
            }
            $purchaseReturn->items()->delete();

            // Filter for items that are actually being returned
            $returnedItems = array_filter($items, function($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            if (empty($returnedItems)) {
                // If you want to allow removing all items, you might handle this differently.
                // For now, let's assume at least one item should remain for an update.
                // Or, we can just proceed and it will result in a zero-total return.
            }

            // Recalculate total amount on the backend
            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['price'] ?? 0) * $itemData['returned_quantity'];
            }

            $purchaseReturn->update([
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
                'user_id' => Auth::id(), // Also update the user who last edited it
            ]);

            foreach ($returnedItems as $itemData) {
                $returnedQuantity = $itemData['returned_quantity'];
                $price = $itemData['price'] ?? 0;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $price,
                    'total' => $price * $returnedQuantity,
                ]);

                // Decrement stock for the returned product
                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $product->decrement('stock_quantity', $returnedQuantity);
                }
            }

            $purchase = $purchaseReturn->purchase;
            // Update original purchase status
            $totalPurchasedQuantity = $purchase->items()->sum('quantity');
            $totalReturnedQuantity = $purchase->purchaseReturns()->with('items')->get()->flatMap(function($pr) {
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


            // Update Journal Entry if it exists, or create a new one.
            // For simplicity, we'll assume a new Journal Entry logic or that accounting is handled separately.
            // A more complex implementation might involve voiding the old entry and creating a new one.

            return $purchaseReturn->fresh(); // Return the updated model with relations
        });
    }
}
