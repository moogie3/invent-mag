<?php

namespace Database\Seeders;

use App\Helpers\PurchaseHelper;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

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
            $this->command->warn('Skipping PurchaseSeeder for tenant '.app('currentTenant')->name.': Missing dependency data.');

            return;
        }

        $this->command->info('Seeding Purchase Orders for tenant: '.app('currentTenant')->name);
        $this->command->info("Available: {$suppliers->count()} suppliers, {$products->count()} products, ".count($warehouseIds).' warehouses');

        // Resolve default tax once — TaxSeeder must run before PurchaseSeeder
        $defaultTax = Tax::where('tenant_id', $tenantId)->where('is_default', true)->first()
            ?? Tax::where('tenant_id', $tenantId)->where('is_active', true)->first();
        $taxId = $defaultTax?->id;
        $taxRate = $defaultTax ? (float) $defaultTax->rate : 0.0;
        $taxType = $defaultTax ? $defaultTax->type : 'none';

        for ($i = 0; $i < 50; $i++) {
            $supplier = $suppliers->random();
            $warehouseId = collect($warehouseIds)->random();
            $orderDate = Carbon::now()->subDays(rand(0, 59));
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Transfer', '-'])->random();
            $status = collect(['Unpaid', 'Paid', 'Partial'])->random();

            $purchase = Purchase::create([
                'invoice' => 'PO-'.str_pad(Purchase::where('tenant_id', $tenantId)->count() + 1, 5, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouseId,
                'order_date' => $orderDate,
                'due_date' => $dueDate,
                'payment_type' => $paymentType,
                'discount_total' => rand(0, 50),
                'discount_total_type' => collect(['percentage', 'fixed'])->random(),
                'tax_id' => $taxId,
                'tax_rate' => $taxRate,
                'tax_type' => $taxType,
                'total' => 0,
                'total_tax' => 0,
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

            // If after maxAttempts totals are still out of range, rescale proportionally
            if ($totalPurchaseAmount < 100) {
                $scaleFactor = rand(100, 1000) / $totalPurchaseAmount;
                foreach ($poItemsData as &$item) {
                    $item['total'] = round($item['total'] * $scaleFactor);
                }
                unset($item);
                $totalPurchaseAmount = array_sum(array_column($poItemsData, 'total'));
            } elseif ($totalPurchaseAmount > 10000) {
                $scaleFactor = rand(8000, 10000) / $totalPurchaseAmount;
                foreach ($poItemsData as &$item) {
                    $item['total'] = round($item['total'] * $scaleFactor);
                }
                unset($item);
                $totalPurchaseAmount = array_sum(array_column($poItemsData, 'total'));
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
                        'tenant_id' => $tenantId,
                    ],
                    ['quantity' => 0]
                );
                $stockRecord->increment('quantity', $itemData['quantity']);
            }

            $purchase->update(['total' => $totalPurchaseAmount]);

            // Compute tax and store the true grand total
            $orderDiscount = PurchaseHelper::calculateDiscount($totalPurchaseAmount, $purchase->discount_total, $purchase->discount_total_type);
            $afterDiscount = $totalPurchaseAmount - $orderDiscount;
            $totalTaxAmount = PurchaseHelper::calculateTaxAmount($afterDiscount, $taxRate, $taxType);
            $grandTotal = ($taxType === 'inclusive') ? $afterDiscount : ($afterDiscount + $totalTaxAmount);

            $purchase->update([
                'total' => $grandTotal,
                'total_tax' => $totalTaxAmount,
            ]);

            $paidAmount = 0;

            // Add payment logic
            if ($grandTotal > 0) {
                if ($status === 'Paid') {
                    $paidAmount = $grandTotal;
                    $purchase->payments()->create([
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => 'Full payment during seeding.',
                        'tenant_id' => $tenantId,
                    ]);
                } elseif ($status === 'Partial') {
                    $paidAmount = rand(1, (int) ($grandTotal * 0.8)); // Pay between 1 and 80%
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

            // Debit Inventory for the grand total (includes tax for exclusive)
            $transactions[] = ['account_code' => '1140-'.$tenantId, 'type' => 'debit', 'amount' => $grandTotal];

            if ($status === 'Paid') {
                // Credit Cash for the full amount
                $transactions[] = ['account_code' => '1110-'.$tenantId, 'type' => 'credit', 'amount' => $grandTotal];
            } elseif ($status === 'Unpaid') {
                // Credit Accounts Payable for the full amount
                $transactions[] = ['account_code' => '2110-'.$tenantId, 'type' => 'credit', 'amount' => $grandTotal];
            } elseif ($status === 'Partial') {
                // Credit Cash for the paid amount
                if ($paidAmount > 0) {
                    $transactions[] = ['account_code' => '1110-'.$tenantId, 'type' => 'credit', 'amount' => $paidAmount];
                }
                // Credit Accounts Payable for the remaining balance
                $remainingBalance = $grandTotal - $paidAmount;
                if ($remainingBalance > 0) {
                    $transactions[] = ['account_code' => '2110-'.$tenantId, 'type' => 'credit', 'amount' => $remainingBalance];
                }
            }

            try {
                $this->accountingService->createJournalEntry($description, $orderDate, $transactions, $purchase);
            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for purchase {$purchase->invoice}: ".$e->getMessage());
            }
        }

        $this->command->info('Purchase Orders seeding completed successfully!');
        $this->command->info('Created 50 purchase orders with items, payments, and journal entries.');
    }
}
