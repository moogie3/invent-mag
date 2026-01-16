<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sales;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use App\Models\SalesItem;
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

        if ($customers->isEmpty() || $users->isEmpty() || $products->isEmpty()) {
            $this->command->info('Skipping SalesSeeder for tenant ' . app('currentTenant')->name . ': No customers, users, or products found. Please run their respective seeders first.');
            return;
        }

        for ($i = 0; $i < 100; $i++) { // Create 100 sample sales
            $customer = $customers->random();
            $user = $users->random();
            $orderDate = Carbon::now()->subMonths(rand(0, 11))->subDays(rand(0, 29)); // Sales over the last 12 months
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Card', 'Transfer', 'eWallet', '-'])->random();
            $status = collect(['Unpaid', 'Paid', 'Partial'])->random();

            $invoiceNumber = $i + 1; // Use loop counter for invoice number
            $invoice = 'INV-' . str_pad($invoiceNumber, 5, '0', STR_PAD_LEFT) . '-' . $tenantId;

            $sales = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'order_date' => $orderDate,
                'due_date' => $dueDate,
                'payment_type' => $paymentType,
                'order_discount' => rand(0, 20),
                'order_discount_type' => collect(['percentage', 'fixed'])->random(),
                'tax_rate' => 10, // Example tax rate
                'total_tax' => 0, // Will be calculated
                'total' => 0, // Will be calculated from items
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
            $tenantName = app('currentTenant')->name;
            try {
                // 1. Record the revenue and accounts receivable
                $revenueTransactions = [
                    ['account_name' => 'accounting.accounts.accounts_receivable.name - ' . $tenantName, 'type' => 'debit', 'amount' => $finalCalculatedTotal],
                    ['account_name' => 'accounting.accounts.sales_revenue.name - ' . $tenantName, 'type' => 'credit', 'amount' => $totalSalesAmount],
                ];
                if ($totalTaxAmount > 0) {
                    $revenueTransactions[] = ['account_name' => 'accounting.accounts.output_vat.name - ' . $tenantName, 'type' => 'credit', 'amount' => $totalTaxAmount];
                }
                $this->accountingService->createJournalEntry("Sale - Invoice {$sales->invoice}", $orderDate, $revenueTransactions, $sales);

                // 2. Record the cost of goods sold
                if ($totalCostOfGoods > 0) {
                    $cogsTransactions = [
                        ['account_name' => 'accounting.accounts.cost_of_goods_sold.name - ' . $tenantName, 'type' => 'debit', 'amount' => $totalCostOfGoods],
                        ['account_name' => 'accounting.accounts.inventory.name - ' . $tenantName, 'type' => 'credit', 'amount' => $totalCostOfGoods],
                    ];
                    $this->accountingService->createJournalEntry("COGS for Invoice {$sales->invoice}", $orderDate, $cogsTransactions, $sales);
                }

                // 3. Record the payment, if any
                if ($paidAmount > 0) {
                    $paymentTransactions = [
                        ['account_name' => 'accounting.accounts.cash.name - ' . $tenantName, 'type' => 'debit', 'amount' => $paidAmount],
                        ['account_name' => 'accounting.accounts.accounts_receivable.name - ' . $tenantName, 'type' => 'credit', 'amount' => $paidAmount],
                    ];
                    $this->accountingService->createJournalEntry("Payment for Invoice {$sales->invoice}", $orderDate, $paymentTransactions, $sales);
                }

            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for sale {$sales->invoice}: " . $e->getMessage());
            }
        }
    }
}