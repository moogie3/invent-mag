<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\POItem;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Services\AccountingService;
use Carbon\Carbon;

class PurchaseSeeder extends Seeder
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $suppliers = Supplier::where('tenant_id', $tenantId)->get();
        $products = Product::where('tenant_id', $tenantId)->get();
        $warehouseIds = Warehouse::where('tenant_id', $tenantId)->pluck('id')->toArray();

        if ($suppliers->isEmpty() || $products->isEmpty() || empty($warehouseIds)) {
            $this->command->info('Skipping PurchaseSeeder for tenant ' . app('currentTenant')->name . ': Missing dependency data.');
            return;
        }

        for ($i = 0; $i < 100; $i++) {
            $supplier = $suppliers->random();
            $warehouseId = collect($warehouseIds)->random();
            $orderDate = Carbon::now()->subDays(rand(0, 29));
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Transfer', '-'])->random();
            $status = collect(['Unpaid', 'Paid', 'Partial'])->random();

            $purchase = Purchase::create([
                'invoice' => 'PO-' . str_pad(Purchase::where('tenant_id', $tenantId)->count() + 1, 5, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouseId, // Added
                'order_date' => $orderDate,
                'due_date' => $dueDate,
                'payment_type' => $paymentType,
                'discount_total' => rand(0, 50),
                'discount_total_type' => collect(['percentage', 'fixed'])->random(),
                'total' => 0,
                'status' => $status,
                'tenant_id' => $tenantId,
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
                    $price = $product->price;
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
            } while (($totalPurchaseAmount < 100 || $totalPurchaseAmount > 10000) && $attempts < $maxAttempts);

            // If after maxAttempts, still not in range, adjust to be within range
            if ($totalPurchaseAmount < 100) {
                $totalPurchaseAmount = rand(100, 1000);
            } elseif ($totalPurchaseAmount > 10000) {
                $totalPurchaseAmount = rand(8000, 10000);
            }

            foreach ($poItemsData as $itemData) {
                $product = Product::find($itemData['product_id']);
                $expiryDate = null;

                if ($product && $product->has_expiry) {
                    // Generate a random expiry date within a reasonable range (e.g., 1 month to 1 year from now)
                    $expiryDate = Carbon::now()->addDays(rand(1, 90));
                }

                POItem::create(array_merge([
                    'po_id' => $purchase->id,
                    'expiry_date' => $expiryDate,
                    'remaining_quantity' => $itemData['quantity'],
                    'tenant_id' => $tenantId,
                ], $itemData));

                // Update product stock in warehouse
                $stockRecord = ProductWarehouse::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $purchase->warehouse_id,
                        'tenant_id' => $tenantId
                    ],
                    ['quantity' => 0]
                );
                $stockRecord->increment('quantity', $itemData['quantity']);
            }

            $purchase->update(['total' => $totalPurchaseAmount]);
            
            $paidAmount = 0;

            // Add payment logic
            if ($totalPurchaseAmount > 0) { // Ensure there's an amount to pay
                if ($status === 'Paid') {
                    $paidAmount = $totalPurchaseAmount;
                    $purchase->payments()->create([
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => 'Full payment during seeding.',
                        'tenant_id' => $tenantId,
                    ]);
                } elseif ($status === 'Partial') {
                    $paidAmount = rand(1, (int)($totalPurchaseAmount * 0.8)); // Pay between 1 and 80%
                    $purchase->payments()->create([
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => 'Partial payment during seeding.',
                        'tenant_id' => $tenantId,
                    ]);
                }
            }

            // Create Journal Entry
            $tenantId = app('currentTenant')->id;
            $transactions = [];
            $description = "Purchase of goods, invoice {$purchase->invoice}";

            // Debit Inventory for the full purchase amount
            $transactions[] = ['account_code' => '1140-' . $tenantId, 'type' => 'debit', 'amount' => $totalPurchaseAmount];

            if ($status === 'Paid') {
                // Credit Cash for the full amount
                $transactions[] = ['account_code' => '1110-' . $tenantId, 'type' => 'credit', 'amount' => $totalPurchaseAmount];
            } elseif ($status === 'Unpaid') {
                // Credit Accounts Payable for the full amount
                $transactions[] = ['account_code' => '2110-' . $tenantId, 'type' => 'credit', 'amount' => $totalPurchaseAmount];
            } elseif ($status === 'Partial') {
                // Credit Cash for the paid amount
                if ($paidAmount > 0) {
                    $transactions[] = ['account_code' => '1110-' . $tenantId, 'type' => 'credit', 'amount' => $paidAmount];
                }
                // Credit Accounts Payable for the remaining balance
                $remainingBalance = $totalPurchaseAmount - $paidAmount;
                if ($remainingBalance > 0) {
                    $transactions[] = ['account_code' => '2110-' . $tenantId, 'type' => 'credit', 'amount' => $remainingBalance];
                }
            }
            
            try {
                $this->accountingService->createJournalEntry($description, $orderDate, $transactions, $purchase);
            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for purchase {$purchase->invoice}: " . $e->getMessage());
            }
        }
    }
}