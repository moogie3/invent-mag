<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesReturn;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\ProductWarehouse;
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
        $tenantId = app('currentTenant')->id;

        Schema::disableForeignKeyConstraints();
        SalesReturn::where('tenant_id', $tenantId)->delete();
        $salesReturnIds = SalesReturn::where('tenant_id', $tenantId)->pluck('id');
        SalesReturnItem::whereIn('sales_return_id', $salesReturnIds)->delete();
        Schema::enableForeignKeyConstraints();

        $users = User::where('tenant_id', $tenantId)->get();
        $sales = Sales::where('tenant_id', $tenantId)->has('salesItems')->get(); // Only sales with items

        if ($users->isEmpty() || $sales->isEmpty()) {
            $this->command->warn('Skipping SalesReturnSeeder for tenant ' . app('currentTenant')->name . ': No users or sales with items found. Please run UserSeeder and SalesSeeder first.');
            return;
        }

        $this->command->info("Seeding Sales Returns for tenant: " . app('currentTenant')->name);
        
        $faker = \Faker\Factory::create();

        $count = 0;
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
                $netUnitPrice = $salesItem->total / $salesItem->quantity;
                $itemTotal = $returnQuantity * $netUnitPrice; 
                $totalReturnAmount += $itemTotal;

                $returnItemsData[] = [
                    'product_id' => $salesItem->product_id,
                    'quantity' => $returnQuantity,
                    'price' => $netUnitPrice,
                    'total' => $itemTotal,
                    'tenant_id' => $tenantId,
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
                'tenant_id' => $tenantId,
            ]);

            // Create SalesReturnItems
            foreach ($returnItemsData as $itemData) {
                $salesReturnItem = $salesReturn->items()->create($itemData);

                // Update product stock (increment as it's a customer return back to inventory)
                $stockRecord = ProductWarehouse::where('product_id', $salesReturnItem->product_id)
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->increment('quantity', $salesReturnItem->quantity);
                } else {
                     ProductWarehouse::create([
                        'product_id' => $salesReturnItem->product_id,
                        'warehouse_id' => $sale->warehouse_id,
                        'quantity' => $salesReturnItem->quantity,
                        'tenant_id' => $tenantId,
                    ]);
                }
            }

            // Create Journal Entry for Sales Return
            $description = "Sales Return for Sales Invoice {$sale->invoice}, Return #{$salesReturn->id}";
            $transactions = [];

            // Debit Sales Returns (or a specific return expense account)
            // Credit Accounts Receivable / Cash (depending on original payment type or refund status)
            $transactions[] = ['account_code' => '4100-' . $tenantId, 'type' => 'debit', 'amount' => $totalReturnAmount]; // Reduce revenue
            $transactions[] = ['account_code' => '1130-' . $tenantId, 'type' => 'credit', 'amount' => $totalReturnAmount]; // Reduce AR

            try {
                $this->accountingService->createJournalEntry($description, Carbon::parse($returnDate), $transactions, $salesReturn);
            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for sales return {$salesReturn->id}: " . $e->getMessage());
            }
            
            $count++;
        }
        
        $this->command->info("Sales Return seeding completed!");
        $this->command->info("Created {$count} sales returns with items, stock updates, and journal entries.");
    }
}