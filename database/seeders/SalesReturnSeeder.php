<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesReturn;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\User;
use App\Models\Product;
use App\Models\SalesReturnItem;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesReturnSeeder extends Seeder
{
    protected AccountingService $accountingService;

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
        Schema::disableForeignKeyConstraints();
        SalesReturn::truncate();
        SalesReturnItem::truncate();
        Schema::enableForeignKeyConstraints();

        $users = User::all();
        $sales = Sales::has('salesItems')->get(); // Only sales with items

        if ($users->isEmpty() || $sales->isEmpty()) {
            $this->command->info('Skipping SalesReturnSeeder: No users or sales with items found. Please run UserSeeder and SalesSeeder first.');
            return;
        }

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 15; $i++) {
            $sale = $sales->random();
            $user = $users->random();
            $returnDate = $faker->dateTimeBetween($sale->order_date, 'now');
            $status = $faker->randomElement(SalesReturn::$statuses);

            // Fetch items from the selected sale
            $salesItems = $sale->salesItems;

            if ($salesItems->isEmpty()) {
                continue; // Skip if selected sale has no items (shouldn't happen with has('salesItems'))
            }

            $returnItemsData = [];
            $totalReturnAmount = 0;

            // Randomly select 1 to min(3, number of items) items to return
            $itemsToReturnCount = rand(1, min(3, $salesItems->count()));
            $selectedSalesItems = $salesItems->random($itemsToReturnCount);

            foreach ($selectedSalesItems as $salesItem) {
                $returnQuantity = rand(1, $salesItem->quantity); // Return a quantity less than or equal to original
                $itemTotal = $returnQuantity * $salesItem->price; // Use salesItem's price, not customer_price
                $totalReturnAmount += $itemTotal;

                $returnItemsData[] = [
                    'product_id' => $salesItem->product_id,
                    'quantity' => $returnQuantity,
                    'price' => $salesItem->price,
                    'total' => $itemTotal,
                ];
            }

            if (empty($returnItemsData)) {
                continue; // Skip if no items were selected to return
            }

            // Create the SalesReturn
            $salesReturn = SalesReturn::create([
                'sales_id' => $sale->id,
                'user_id' => $user->id,
                'return_date' => $returnDate,
                'reason' => $faker->sentence(),
                'total_amount' => $totalReturnAmount,
                'status' => $status,
            ]);

            // Create SalesReturnItems
            foreach ($returnItemsData as $itemData) {
                $salesReturnItem = $salesReturn->items()->create($itemData);

                // Update product stock (decrement as it's a customer return back to inventory)
                $product = Product::find($salesReturnItem->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $salesReturnItem->quantity);
                }
            }

            // Create Journal Entry for Sales Return
            $description = "Sales Return for Sales Invoice {$sale->invoice}, Return #{$salesReturn->id}";
            $transactions = [];

            // Debit Sales Returns (or a specific return expense account)
            // Credit Accounts Receivable / Cash (depending on original payment type or refund status)
            $transactions[] = ['account_name' => 'accounting.accounts.sales_revenue.name', 'type' => 'debit', 'amount' => $totalReturnAmount]; // Reduce revenue
            $transactions[] = ['account_name' => 'accounting.accounts.accounts_receivable.name', 'type' => 'credit', 'amount' => $totalReturnAmount]; // Reduce AR

            try {
                $this->accountingService->createJournalEntry($description, Carbon::parse($returnDate), $transactions, $salesReturn);
            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for sales return {$salesReturn->id}: " . $e->getMessage());
            }
        }
    }
}