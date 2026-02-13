<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\POItem;
use App\Models\User;
use App\Models\Product;
use App\Models\PurchaseReturnItem;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseReturnSeeder extends Seeder
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
        PurchaseReturn::where('tenant_id', $tenantId)->delete();
        $purchaseReturnIds = PurchaseReturn::where('tenant_id', $tenantId)->pluck('id');
        PurchaseReturnItem::whereIn('purchase_return_id', $purchaseReturnIds)->delete();
        Schema::enableForeignKeyConstraints();

        $users = User::where('tenant_id', $tenantId)->get();
        $purchases = Purchase::where('tenant_id', $tenantId)->has('items')->get(); // Only purchases with items

        if ($users->isEmpty() || $purchases->isEmpty()) {
            $this->command->warn('Skipping PurchaseReturnSeeder for tenant ' . app('currentTenant')->name . ': No users or purchases with items found. Please run UserSeeder and PurchaseSeeder first.');
            return;
        }

        $this->command->info("Seeding Purchase Returns for tenant: " . app('currentTenant')->name);

        $faker = \Faker\Factory::create();

        $count = 0;
        for ($i = 0; $i < 15; $i++) {
            $purchase = $purchases->random();
            $user = $users->random();
            $returnDate = $faker->dateTimeBetween($purchase->order_date, 'now');
            $status = $faker->randomElement(PurchaseReturn::$statuses);

            // Fetch items from the selected purchase
            $poItems = $purchase->items;

            if ($poItems->isEmpty()) {
                continue; // Skip if selected purchase has no items (shouldn't happen with has('poItems'))
            }

            $returnItemsData = [];
            $totalReturnAmount = 0;

            // Randomly select 1 to min(3, number of items) items to return
            $itemsToReturnCount = rand(1, min(3, $poItems->count()));
            $selectedPoItems = $poItems->random($itemsToReturnCount);

            foreach ($selectedPoItems as $poItem) {
                $returnQuantity = rand(1, $poItem->quantity); // Return a quantity less than or equal to original
                $netUnitPrice = $poItem->total / $poItem->quantity;
                $itemTotal = $returnQuantity * $netUnitPrice;
                $totalReturnAmount += $itemTotal;

                $returnItemsData[] = [
                    'product_id' => $poItem->product_id,
                    'quantity' => $returnQuantity,
                    'price' => $netUnitPrice,
                    'total' => $itemTotal,
                    'tenant_id' => $tenantId,
                ];
            }

            if (empty($returnItemsData)) {
                continue; // Skip if no items were selected to return
            }

            // Create the PurchaseReturn
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'return_date' => $returnDate,
                'reason' => $faker->sentence(),
                'total_amount' => $totalReturnAmount,
                'status' => $status,
                'tenant_id' => $tenantId,
            ]);

            // Create PurchaseReturnItems
            foreach ($returnItemsData as $itemData) {
                $purchaseReturnItem = $purchaseReturn->items()->create($itemData);

                // Update product stock (increment as it's a return to stock)
                // Note: Purchase return usually means returning TO supplier, so stock should DECREMENT.
                // But if the logic implies reversing a purchase, it depends on when stock was added.
                // Assuming Purchase adds to stock, Return reduces stock.
                
                $stockRecord = \App\Models\ProductWarehouse::where('product_id', $purchaseReturnItem->product_id)
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->decrement('quantity', $purchaseReturnItem->quantity);
                }
            }

            // Create Journal Entry for Purchase Return
            $description = "Purchase Return for PO {$purchase->invoice}, Return #{$purchaseReturn->id}";
            $transactions = [];

            // Debit Accounts Payable / Cash (depending on original payment type or refund status)
            // For simplicity, we'll assume a credit to a Purchase Returns account and debit to AP/Cash
            // More complex logic might be needed here based on refund status
            $transactions[] = ['account_code' => '2110-' . $tenantId, 'type' => 'debit', 'amount' => $totalReturnAmount];
            $transactions[] = ['account_code' => '1140-' . $tenantId, 'type' => 'credit', 'amount' => $totalReturnAmount]; // Adjust inventory

            try {
                $this->accountingService->createJournalEntry($description, Carbon::parse($returnDate), $transactions, $purchaseReturn);
            } catch (\Exception $e) {
                $this->command->error("Failed to create journal entry for purchase return {$purchaseReturn->id}: " . $e->getMessage());
            }
            
            $count++;
        }
        
        $this->command->info("Purchase Return seeding completed!");
        $this->command->info("Created {$count} purchase returns with items, stock updates, and journal entries.");
    }
}