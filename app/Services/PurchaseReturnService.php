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
use Dompdf\Dompdf;
use App\Helpers\CurrencyHelper;

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

    public function createPurchaseReturn(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::findOrFail($data['purchase_id']);
            $items = json_decode($data['items'], true);
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            // Filter for items that are actually being returned
            $returnedItems = array_filter($items, function($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            if (empty($returnedItems)) {
                throw new \Exception('No items selected for return.');
            }

            // Recalculate total amount on the backend to ensure data integrity
            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['return_price'] ?? $itemData['price']) * $itemData['returned_quantity'];
            }

            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'user_id' => Auth::id(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
            ]);

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

                // Decrement stock for the returned product
                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $product->decrement('stock_quantity', $returnedQuantity);
                }
            }

            // Update original purchase status
            $totalPurchasedQuantity = $purchase->items->sum('quantity');
            $totalReturnedQuantity = $purchase->purchaseReturns()->with('items')->get()->flatMap->items->sum('quantity');


            if ($totalReturnedQuantity >= $totalPurchasedQuantity) {
                $purchase->update(['status' => 'Returned']);
            } elseif ($totalReturnedQuantity > 0) {
                $purchase->update(['status' => 'Partial']);
            }

            // Create Journal Entry for the return
            $accountingSettings = Auth::user()->accounting_settings;
            if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['accounts_payable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts payable are not configured.');
            }

            $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);
            $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id']);

            if (!$inventoryAccount || !$accountsPayableAccount) {
                throw new \Exception('Required accounts (Inventory, Accounts Payable) not found for Purchase Return. Please check Accounting Settings.');
            }

            $inventoryAccountName = $inventoryAccount->name;
            $accountsPayableAccountName = $accountsPayableAccount->name;

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
                'reason' => $data['reason'] ?? null, // Add reason field here
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
            $dompdf = new Dompdf();
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
                        \Carbon\Carbon::parse($pr->return_date)->format('Y-m-d'),
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

    public function printReturn($id)
    {
        $por = PurchaseReturn::with(['purchase.supplier', 'items.product', 'user'])->findOrFail($id);
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        $html = view('admin.por.print-pdf', compact('por', 'shopname', 'address'))->render();
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('purchase-return-' . $por->id . '.pdf', ['Attachment' => false]);
    }
}
