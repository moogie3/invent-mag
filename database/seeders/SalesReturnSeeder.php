<?php

namespace Database\Seeders;

use App\Models\Sales;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\SalesReturnService;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class SalesReturnSeeder extends Seeder
{
    public function __construct(protected SalesReturnService $salesReturnService) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $existingReturns = SalesReturn::where('tenant_id', $tenantId)->count();
        if ($existingReturns > 0) {
            $this->command->info('Sales returns already exist for tenant '.app('currentTenant')->name.'. Skipping to preserve existing data.');

            return;
        }

        $users = User::where('tenant_id', $tenantId)->get();
        $sales = Sales::where('tenant_id', $tenantId)->has('salesItems')->get(); // Only sales with items

        if ($users->isEmpty() || $sales->isEmpty()) {
            $this->command->warn('Skipping SalesReturnSeeder for tenant '.app('currentTenant')->name.': No users or sales with items found. Please run UserSeeder and SalesSeeder first.');

            return;
        }

        $this->command->info('Seeding Sales Returns for tenant: '.app('currentTenant')->name);

        $faker = Factory::create();

        $count = 0;
        // Units already handed back per sale and product. The loop draws a
        // random sale up to 15 times, so one invoice can come up repeatedly;
        // without this the seeder could return more units than the sale ever
        // shipped. SalesReturnService does not enforce that ceiling itself
        // (it only recomputes the totals it is given), so the allowance has
        // to be held here. A return born Canceled holds no stock, so it never
        // consumes the allowance.
        $returnedSoFar = [];

        for ($i = 0; $i < 15; $i++) {
            $sale = $sales->random();
            $user = $users->random();

            // SalesReturnService takes the return's owner from the
            // authenticated user and sales_returns.user_id is NOT NULL, so the
            // randomly chosen user has to be set explicitly or the insert is
            // rejected.
            Auth::setUser($user);

            $returnDate = $faker->dateTimeBetween($sale->order_date, 'now');
            $status = $faker->randomElement(SalesReturn::$statuses);

            // A return created as Canceled holds no stock and no live journal
            // entry: the cancel path unwinds both, so a row born Canceled must
            // never have moved stock or booked an entry in the first place.
            $isCanceled = $status === 'Canceled';

            // Fetch items from the selected sale
            $salesItems = $sale->salesItems;

            if ($salesItems->isEmpty()) {
                continue; // Skip if selected sale has no items (shouldn't happen with has('salesItems'))
            }

            // One sale can carry the same product on more than one line, so the
            // allowance is counted per product across all of that sale's lines.
            $soldByProduct = $salesItems->groupBy('product_id')
                ->map(fn ($lines) => (int) $lines->sum('quantity'));

            $returnItemsData = [];

            // Randomly select 1 to min(3, number of items) items to return
            $itemsToReturnCount = rand(1, min(3, $salesItems->count()));
            $selectedSalesItems = $salesItems->random($itemsToReturnCount);

            foreach ($selectedSalesItems as $salesItem) {
                $productId = $salesItem->product_id;
                $alreadyReturned = $returnedSoFar[$sale->id][$productId] ?? 0;
                $remaining = (int) ($soldByProduct[$productId] ?? 0) - $alreadyReturned;

                // Everything this sale ever shipped of this product has already
                // been returned, so there is nothing left to hand back.
                if ($remaining <= 0) {
                    continue;
                }

                // Bounded by the line as well as by the product-wide budget:
                // $remaining can be far larger than this line sold, and a
                // return for more than one line sold is as wrong as a return
                // for more than the sale sold.
                $returnQuantity = rand(1, min((int) $salesItem->quantity, $remaining));
                if (! $isCanceled) {
                    $returnedSoFar[$sale->id][$productId] = $alreadyReturned + $returnQuantity;
                }

                // Priced off the line the goods came from, so a return of a
                // discounted line hands back what that line actually charged
                // rather than the product's list price.
                $netUnitPrice = $salesItem->total / $salesItem->quantity;

                $returnItemsData[] = [
                    'sales_item_id' => $salesItem->id,
                    'product_id' => $salesItem->product_id,
                    'price' => $netUnitPrice,
                    'returned_quantity' => $returnQuantity,
                ];
            }

            if (empty($returnItemsData)) {
                continue; // Skip if no items were selected to return
            }

            // The service copies the tax snapshot off the parent sale, moves
            // the stock back, restores the batches it was drawn from, books
            // the reversal off the original posting, updates the sale's status
            // and raises the customer credit when the invoice had been paid.
            // None of that was happening here: this seeder wrote the row, the
            // stock and a flat Dr Sales / Cr AR entry by hand.
            try {
                $this->salesReturnService->createSalesReturn([
                    'sales_id' => $sale->id,
                    'return_date' => Carbon::instance($returnDate),
                    'reason' => $faker->sentence(),
                    'status' => $status,
                    'items' => json_encode($returnItemsData),
                ]);
            } catch (\Exception $e) {
                $this->command->error("Failed to create sales return against invoice {$sale->invoice}: ".$e->getMessage());

                continue;
            }

            $count++;
        }

        $this->command->info('Sales Return seeding completed!');
        $this->command->info("Created {$count} sales returns with items, stock updates, and journal entries.");
        $this->command->info('Canceled returns carry items only: no stock move and no journal entry.');
    }
}
