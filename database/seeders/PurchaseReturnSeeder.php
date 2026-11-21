<?php

namespace Database\Seeders;

use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\User;
use App\Services\PurchaseReturnService;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnSeeder extends Seeder
{
    public function __construct(protected PurchaseReturnService $purchaseReturnService) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $existingReturns = PurchaseReturn::where('tenant_id', $tenantId)->count();
        if ($existingReturns > 0) {
            $this->command->info('Purchase returns already exist for tenant '.app('currentTenant')->name.'. Skipping to preserve existing data.');

            return;
        }

        $users = User::where('tenant_id', $tenantId)->get();
        $purchases = Purchase::where('tenant_id', $tenantId)->has('items')->get(); // Only purchases with items

        if ($users->isEmpty() || $purchases->isEmpty()) {
            $this->command->warn('Skipping PurchaseReturnSeeder for tenant '.app('currentTenant')->name.': No users or purchases with items found. Please run UserSeeder and PurchaseSeeder first.');

            return;
        }

        $this->command->info('Seeding Purchase Returns for tenant: '.app('currentTenant')->name);

        $faker = Factory::create();

        $count = 0;
        // Units already returned per purchase and product. The loop draws a
        // random PO up to 15 times, so the same order can come up repeatedly;
        // without this the seeder could return more units than the PO ever
        // contained. PurchaseReturnService does not enforce that ceiling
        // itself, so the allowance has to be held here. A return born Canceled
        // holds no stock, so it never consumes the allowance.
        $returnedSoFar = [];

        for ($i = 0; $i < 15; $i++) {
            $purchase = $purchases->random();
            $user = $users->random();

            // PurchaseReturnService takes the return's owner from the
            // authenticated user and purchase_returns.user_id is NOT NULL, so
            // the randomly chosen user has to be set explicitly or the insert
            // is rejected.
            Auth::setUser($user);

            $returnDate = $faker->dateTimeBetween($purchase->order_date, 'now');
            $status = $faker->randomElement(PurchaseReturn::$statuses);

            // A return created as Canceled holds no stock and no live journal
            // entry: the cancel path unwinds both, so a row born Canceled must
            // never have moved stock or booked an entry in the first place.
            $isCanceled = $status === 'Canceled';

            // Fetch items from the selected purchase
            $poItems = $purchase->items;

            if ($poItems->isEmpty()) {
                continue; // Skip if selected purchase has no items (shouldn't happen with has('items'))
            }

            // One PO can carry the same product on more than one line, so the
            // allowance is counted per product across all of that PO's lines.
            $orderedByProduct = $poItems->groupBy('product_id')
                ->map(fn ($lines) => (int) $lines->sum('quantity'));

            $returnItemsData = [];
            // Quantities already chosen for this return but not yet written to
            // the warehouse: the decrement only runs once the return row
            // exists, so the live stock figure below would otherwise look
            // untouched and every line of the same product would be allowed to
            // take the whole balance.
            $pendingDraw = [];

            // Randomly select 1 to min(3, number of items) items to return
            $itemsToReturnCount = rand(1, min(3, $poItems->count()));
            $selectedPoItems = $poItems->random($itemsToReturnCount);

            foreach ($selectedPoItems as $poItem) {
                $productId = $poItem->product_id;
                $alreadyReturned = $returnedSoFar[$purchase->id][$productId] ?? 0;
                $remaining = (int) ($orderedByProduct[$productId] ?? 0) - $alreadyReturned;

                // Everything this PO ordered of this product has already been
                // returned, so there is nothing left to hand back.
                if ($remaining <= 0) {
                    continue;
                }

                // You can only hand back what the warehouse still holds. The
                // decrement the service performs has no guard of its own, so a
                // return larger than the stock record drove
                // product_warehouse.quantity negative in seeded data
                // (ProductSeeder starts some products at 0). A Canceled return
                // is exempt because it moves nothing. Stock is read live
                // because earlier iterations of this loop have already taken
                // their share out of it.
                if (! $isCanceled) {
                    $inStock = (int) ProductWarehouse::where('product_id', $productId)
                        ->where('warehouse_id', $purchase->warehouse_id)
                        ->where('tenant_id', $tenantId)
                        ->value('quantity');
                    $remaining = min($remaining, $inStock - ($pendingDraw[$productId] ?? 0));

                    if ($remaining <= 0) {
                        continue;
                    }
                }

                // Bounded by the line as well as by the PO-wide budget:
                // $remaining can be far larger than this line ordered, and a
                // return for more than one line ordered is as wrong as a
                // return for more than the PO ordered.
                $returnQuantity = rand(1, min((int) $poItem->quantity, $remaining));
                if (! $isCanceled) {
                    $returnedSoFar[$purchase->id][$productId] = $alreadyReturned + $returnQuantity;
                    $pendingDraw[$productId] = ($pendingDraw[$productId] ?? 0) + $returnQuantity;
                }

                // Priced off the line the goods came from, so a return of a
                // discounted line hands back what that line actually charged
                // rather than the product's list price.
                $netUnitPrice = $poItem->total / $poItem->quantity;

                $returnItemsData[] = [
                    'product_id' => $poItem->product_id,
                    'price' => $netUnitPrice,
                    'returned_quantity' => $returnQuantity,
                ];
            }

            if (empty($returnItemsData)) {
                continue; // Skip if no items were selected to return
            }

            // The service copies the tax snapshot off the parent order, takes
            // the stock and the batches back, books a proportional reversal of
            // whatever the purchase actually posted (including any input VAT
            // and withholding, which the flat Dr AP / Cr Inventory entry this
            // seeder used to write by hand never touched) and updates the
            // order's status.
            try {
                $this->purchaseReturnService->createPurchaseReturn([
                    'purchase_id' => $purchase->id,
                    'return_date' => Carbon::instance($returnDate),
                    'reason' => $faker->sentence(),
                    'status' => $status,
                    'items' => json_encode($returnItemsData),
                ]);
            } catch (\Exception $e) {
                $this->command->error("Failed to create purchase return against PO {$purchase->invoice}: ".$e->getMessage());

                continue;
            }

            $count++;
        }

        $this->command->info('Purchase Return seeding completed!');
        $this->command->info("Created {$count} purchase returns with items, stock updates, and journal entries.");
        $this->command->info('Canceled returns carry items only: no stock move and no journal entry.');
    }
}
