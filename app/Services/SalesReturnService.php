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



    public function createSalesReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $items = json_decode($data['items'], true);

            if (empty($items)) {
                throw new \Exception('No items selected for return.');
            }

            $totalAmount = array_reduce($items, function ($carry, $item) {
                return $carry + ($item['return_price'] * $item['returned_quantity']);
            }, 0);

            $salesReturn = SalesReturn::create([
                'sales_id' => $data['sales_id'],
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'status' => $data['status'],
                'total_amount' => $totalAmount,
                'user_id' => Auth::id(),
            ]);

            foreach ($items as $itemData) {
                if ($itemData['returned_quantity'] > 0) {
                    SalesReturnItem::create([
                        'sales_return_id' => $salesReturn->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['returned_quantity'],
                        'price' => $itemData['return_price'],
                        'total' => $itemData['return_price'] * $itemData['returned_quantity'],
                    ]);

                    $product = Product::find($itemData['product_id']);
                    $product->increment('stock_quantity', $itemData['returned_quantity']);
                }
            }

            $sale = Sales::find($data['sales_id']);
            $totalOriginalQuantitySold = $sale->salesItems()->sum('quantity');
            $totalQuantityReturnedSoFar = $sale->salesReturns->flatMap(function ($sr) {
                return $sr->items;
            })->sum('quantity');

            if ($totalQuantityReturnedSoFar >= $totalOriginalQuantitySold) {
                $sale->status = 'Returned';
            } elseif ($totalQuantityReturnedSoFar > 0) {
                $sale->status = 'Partial';
            } else {
                // Do nothing, leave the status as is
            }
            $sale->save();

            // Create journal entry for sales return
            $this->accountingService->createJournalEntry(
                'Sales return recorded',
                Carbon::parse($salesReturn->return_date),
                [
                    ['account_name' => 'Sales Returns', 'type' => 'debit', 'amount' => $totalAmount],
                    ['account_name' => 'Accounts Receivable', 'type' => 'credit', 'amount' => $totalAmount],
                ],
                $salesReturn
            );

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

            foreach ($salesReturn->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $oldItem->quantity);
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

            $salesReturn->update([
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
                'user_id' => Auth::id(),
            ]);

            foreach ($returnedItems as $itemData) {
                $returnedQuantity = $itemData['returned_quantity'];
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
            $totalOriginalQuantitySold = $sale->salesItems()->sum('quantity');
            $totalQuantityReturnedSoFar = $sale->salesReturns->flatMap(function ($sr) {
                return $sr->items;
            })->sum('quantity');

            if ($totalQuantityReturnedSoFar >= $totalOriginalQuantitySold) {
                $sale->update(['status' => 'Returned']);
            } elseif ($totalQuantityReturnedSoFar > 0) {
                $sale->update(['status' => 'Partial']);
            } else {
                // Do nothing, leave the status as is
            }

            return $salesReturn->fresh();
        });
    }

    public function bulkExportSalesReturns(array $ids, string $exportOption)
    {
        $salesReturns = SalesReturn::with(['sale', 'user'])->whereIn('id', $ids)->get();

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
                        $sr->return_date->format('Y-m-d'),
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

    public function bulkCompleteSalesReturns(array $ids): void
    {
        SalesReturn::whereIn('id', $ids)->update(['status' => 'Completed']);
    }

    public function bulkCancelSalesReturns(array $ids): void
    {
        SalesReturn::whereIn('id', $ids)->update(['status' => 'Canceled']);
    }
}
