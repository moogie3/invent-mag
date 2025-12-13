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

class SalesReturnService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
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



    public function updateSalesReturn(SalesReturn $salesReturn, array $data): SalesReturn
    {
        return DB::transaction(function () use ($salesReturn, $data) {
            $items = json_decode($data['items'], true);
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            foreach ($salesReturn->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $oldItem->quantity);
                }
            }
            $salesReturn->items()->delete();

            $returnedItems = array_filter($items, function($item) {
                return isset($item['quantity']) && $item['quantity'] > 0;
            });

            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['price'] ?? 0) * $itemData['quantity'];
            }

            $salesReturn->update([
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
                'user_id' => Auth::id(),
            ]);

            foreach ($returnedItems as $itemData) {
                $returnedQuantity = $itemData['quantity'];
                $price = $itemData['price'] ?? 0;

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $price,
                    'total' => $price * $returnedQuantity,
                ]);

                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $returnedQuantity);
                }
            }

            $sale = $salesReturn->sale;
            $totalSoldQuantity = $sale->salesItems()->sum('quantity');
            $totalReturnedQuantity = $sale->salesReturns()->with('items')->get()->flatMap(function($sr) {
                return $sr->items;
            })->sum('quantity');

            if ($totalReturnedQuantity >= $totalSoldQuantity) {
                $sale->update(['status' => 'Returned']);
            } elseif ($totalReturnedQuantity > 0) {
                $sale->update(['status' => 'Partial']);
            }

            return $salesReturn->fresh();
        });
    }
}