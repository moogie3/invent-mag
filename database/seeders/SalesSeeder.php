<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sales;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use App\Models\SalesItem;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Services\AccountingService;
use Carbon\Carbon;

use Illuminate\Support\Facades\Schema;

class SalesSeeder extends Seeder
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

        Schema::disableForeignKeyConstraints();
        Sales::where('tenant_id', $tenantId)->delete();
        // We can't truncate SalesItem directly without a tenant_id, so we'll delete them based on the sales of the current tenant
        $salesIds = Sales::where('tenant_id', $tenantId)->pluck('id');
        SalesItem::whereIn('sales_id', $salesIds)->delete();
        Schema::enableForeignKeyConstraints();

        $customers = Customer::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->get();
        $products = Product::where('tenant_id', $tenantId)->get();

        $warehouseIds = Warehouse::where('tenant_id', $tenantId)->pluck('id')->toArray(); // Fetch warehouse IDs

        if ($customers->isEmpty() || $users->isEmpty() || $products->isEmpty() || empty($warehouseIds)) {
            $this->command->warn('Skipping SalesSeeder for tenant ' . app('currentTenant')->name . ': Missing dependency data.');
            return;
        }
        
        $this->command->info("Seeding Sales data for tenant: " . app('currentTenant')->name);
        $this->command->info("Available: {$customers->count()} customers, {$users->count()} users, {$products->count()} products, " . count($warehouseIds) . " warehouses");

        for ($i = 0; $i < 100; $i++) { // Create 100 sample sales
            $customer = $customers->random();
            $user = $users->random();
            $warehouseId = collect($warehouseIds)->random(); // Pick a warehouse
            $orderDate = Carbon::now()->subMonths(rand(0, 11))->subDays(rand(0, 29));
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Card', 'Transfer', 'eWallet', '-'])->random();
            $status = collect(['Unpaid', 'Paid', 'Partial'])->random();

            $invoiceNumber = $i + 1;
            $invoice = 'INV-' . str_pad($invoiceNumber, 5, '0', STR_PAD_LEFT) . '-' . $tenantId;

            $sales = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'warehouse_id' => $warehouseId, // Added warehouse
                'order_date' => $orderDate,
                'due_date' => $dueDate,
                'payment_type' => $paymentType,
                'order_discount' => rand(0, 20),
                'order_discount_type' => collect(['percentage', 'fixed'])->random(),
                'tax_rate' => 10,
                'total_tax' => 0,
                'total' => 0,
                'status' => $status,
                'is_pos' => false,
                'tenant_id' => $tenantId,
            ]);

            $totalSalesAmount = 0;
            $totalTaxAmount = 0;
            $totalCostOfGoods = 0;
            $salesItemsData = [];
            $attempts = 0;
            $maxAttempts = 100; // Prevent infinite loops

            do {
                $totalSalesAmount = 0;
                $totalCostOfGoods = 0;
                $salesItemsData = [];
                $numberOfItems = rand(1, 5);

                for ($j = 0; $j < $numberOfItems; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 5);
                    $customerPrice = $product->selling_price;
                    $total = $quantity * $customerPrice;
                    $totalSalesAmount += $total;
                    $totalCostOfGoods += $quantity * $product->price;

                    $salesItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'customer_price' => $customerPrice,
                        'discount' => 0,
                        'discount_type' => 'fixed',
                        'total' => $total,
                        'tenant_id' => $tenantId,
                    ];
                }

                // Apply discount for calculation purposes
                $discountAmount = 0;
                if ($sales->order_discount_type === 'percentage') {
                    $discountAmount = ($totalSalesAmount * $sales->order_discount) / 100;
                } else {
                    $discountAmount = $sales->order_discount;
                }
                $currentTotalAfterDiscount = $totalSalesAmount - $discountAmount;

                // Apply tax for calculation purposes
                $currentTotalTaxAmount = ($currentTotalAfterDiscount * $sales->tax_rate) / 100;
                $finalCalculatedTotal = $currentTotalAfterDiscount + $currentTotalTaxAmount;

                $attempts++;
            } while (($finalCalculatedTotal < 100 || $finalCalculatedTotal > 10000) && $attempts < $maxAttempts);

            // If after maxAttempts, still not in range, adjust to be within range
            if ($finalCalculatedTotal < 100) {
                $finalCalculatedTotal = rand(100, 1000);
            } elseif ($finalCalculatedTotal > 10000) {
                $finalCalculatedTotal = rand(8000, 10000);
            }

            // Recalculate total_tax based on the adjusted final total (simplified for seeding)
            $totalTaxAmount = ($finalCalculatedTotal * $sales->tax_rate) / (100 + $sales->tax_rate); // Reverse calculate tax
            $totalSalesAmount = $finalCalculatedTotal - $totalTaxAmount;

            foreach ($salesItemsData as $itemData) {
                SalesItem::create(array_merge(['sales_id' => $sales->id], $itemData));
                
                // Decrement stock from the selected warehouse (if it exists in seeding context)
                // For seeding, assume stock was added via Purchase first. If not, this goes negative.
                // To fix negative stock in seeders, we should ensure PurchaseSeeder runs BEFORE SalesSeeder
                // OR initialize stock with a positive value.
                
                $stockRecord = ProductWarehouse::where('product_id', $itemData['product_id'])
                    ->where('warehouse_id', $sales->warehouse_id)
                    ->where('tenant_id', $tenantId)
                    ->first();
                
                if ($stockRecord) {
                    // Ensure enough stock exists before selling to avoid negative numbers in seed
                    if ($stockRecord->quantity < $itemData['quantity']) {
                         $stockRecord->update(['quantity' => $stockRecord->quantity + 100]);
                    }
                    $stockRecord->decrement('quantity', $itemData['quantity']);
                } else {
                    // Initialize with POSITIVE stock to avoid negative numbers during initial seed
                    ProductWarehouse::create([
                        'product_id' => $itemData['product_id'],
                        'warehouse_id' => $sales->warehouse_id,
                        'quantity' => 100, // Start with 100, then decrement will make it 100 - sold
                        'tenant_id' => $tenantId
                    ]);
                    // Re-fetch to decrement properly
                    $stockRecord = \App\Models\ProductWarehouse::where('product_id', $itemData['product_id'])
                        ->where('warehouse_id', $sales->warehouse_id)
                        ->where('tenant_id', $tenantId)
                        ->first();
                    $stockRecord->decrement('quantity', $itemData['quantity']);
                }
            }

            $sales->update([
                'total' => $finalCalculatedTotal,
                'total_tax' => $totalTaxAmount,
                'change_amount' => 0, // No change amount for non-POS sales
            ]);

            $paidAmount = 0;
            // Add payment logic
            if ($finalCalculatedTotal > 0) { // Ensure there's an amount to pay
                if ($status === 'Paid') {
                    $paidAmount = $finalCalculatedTotal;
                    $sales->payments()->create([
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => 'Full payment during seeding.',
                        'tenant_id' => $tenantId,
                    ]);
                } elseif ($status === 'Partial') {
                    $paidAmount = rand(1, (int)($finalCalculatedTotal * 0.8)); // Pay between 1 and 80%
                    $sales->payments()->create([
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => 'Partial payment during seeding.',
                        'tenant_id' => $tenantId,
                    ]);
                }
            }

            // Create Journal Entry for the sale
            $tenantId = app('currentTenant')->id;
            try {
                // 1. Record the revenue and accounts receivable
                $revenueTransactions = [
                    ['account_code' => '1130-' . $tenantId, 'type' => 'debit', 'amount' => $finalCalculatedTotal],
                    ['account_code' => '4100-' . $tenantId, 'type' => 'credit', 'amount' => $totalSalesAmount],
                ];
                if ($totalTaxAmount > 0) {
                    $revenueTransactions[] = ['account_code' => '2130-' . $tenantId, 'type' => 'credit', 'amount' => $totalTaxAmount];
                }
                $this->accountingService->createJournalEntry("Sale - Invoice {$sales->invoice}", $orderDate, $revenueTransactions, $sales);

                // 2. Record the cost of goods sold
                if ($totalCostOfGoods > 0) {
                    $cogsTransactions = [
                        ['account_code' => '5200-' . $tenantId, 'type' => 'debit', 'amount' => $totalCostOfGoods],
                        ['account_code' => '1140-' . $tenantId, 'type' => 'credit', 'amount' => $totalCostOfGoods],
                    ];
                    $this->accountingService->createJournalEntry("COGS for Invoice {$sales->invoice}", $orderDate, $cogsTransactions, $sales);
                }

                // 3. Record the payment, if any
                if ($paidAmount > 0) {
                    $paymentTransactions = [
                        ['account_code' => '1110-' . $tenantId, 'type' => 'debit', 'amount' => $paidAmount],
                        ['account_code' => '1130-' . $tenantId, 'type' => 'credit', 'amount' => $paidAmount],
                    ];
                    $this->accountingService->createJournalEntry("Payment for Invoice {$sales->invoice}", $orderDate, $paymentTransactions, $sales);
                }

            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for sale {$sales->invoice}: " . $e->getMessage());
            }
        }
        
        $this->command->info("Sales seeding completed successfully!");
        $this->command->info("Created 100 sales invoices with items, payments, and journal entries.");
    }
}