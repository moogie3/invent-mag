<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\POItem;
use Carbon\Carbon;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $suppliers = Supplier::all();
        $products = Product::all();

        if ($suppliers->isEmpty() || $products->isEmpty()) {
            $this->command->info('Skipping PurchaseSeeder: No suppliers or products found. Please run SupplierSeeder and ProductSeeder first.');
            return;
        }

        for ($i = 0; $i < 10; $i++) {
            $supplier = $suppliers->random();
            $orderDate = Carbon::now()->subDays(rand(1, 60));
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Transfer', '-'])->random();
            $status = collect(['Unpaid', 'Paid'])->random();

            $purchase = Purchase::create([
                'invoice' => 'PO-' . str_pad(Purchase::count() + 1, 5, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'order_date' => $orderDate,
                'due_date' => $dueDate,
                'payment_type' => $paymentType,
                'discount_total' => rand(0, 50),
                'discount_total_type' => collect(['percentage', 'fixed'])->random(),
                'total' => 0, // Will be calculated from items
                'status' => $status,
                'payment_date' => ($status === 'Paid') ? $orderDate->copy()->addDays(rand(0, 5)) : null,
            ]);

            $totalPurchaseAmount = 0;
            $attempts = 0;
            $maxAttempts = 100; // Prevent infinite loops

            do {
                $totalPurchaseAmount = 0;
                $poItemsData = [];
                $numberOfItems = rand(1, 5);

                for ($j = 0; $j < $numberOfItems; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 10);
                    $price = $product->purchase_price + rand(0, 5);
                    $total = $quantity * $price;
                    $totalPurchaseAmount += $total;

                    $poItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $total,
                    ];
                }
                $attempts++;
            } while (($totalPurchaseAmount < 1000 || $totalPurchaseAmount > 10000) && $attempts < $maxAttempts);

            // If after maxAttempts, still not in range, adjust to be within range
            if ($totalPurchaseAmount < 1000) {
                $totalPurchaseAmount = rand(1000, 2000);
            } elseif ($totalPurchaseAmount > 10000) {
                $totalPurchaseAmount = rand(8000, 10000);
            }

            foreach ($poItemsData as $itemData) {
                POItem::create(array_merge(['po_id' => $purchase->id], $itemData));
            }

            $purchase->update(['total' => $totalPurchaseAmount]);
        }
    }
}
