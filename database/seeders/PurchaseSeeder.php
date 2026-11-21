<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class PurchaseSeeder extends Seeder
{
    public function __construct(protected PurchaseService $purchaseService) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $suppliers = Supplier::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->get();
        $products = Product::where('tenant_id', $tenantId)->get();
        $warehouseIds = Warehouse::where('tenant_id', $tenantId)->pluck('id')->toArray();

        // Same guard SalesSeeder has always had: without it a second db:seed
        // appended another 50 purchase orders per tenant while SalesSeeder
        // skipped, so a re-run left purchases and sales out of step with each
        // other and duplicated every journal entry they book.
        $existingPurchases = Purchase::where('tenant_id', $tenantId)->count();
        if ($existingPurchases > 0) {
            $this->command->info('Purchases already exist for tenant '.app('currentTenant')->name.'. Skipping to preserve existing data.');

            return;
        }

        if ($suppliers->isEmpty() || $users->isEmpty() || $products->isEmpty() || empty($warehouseIds)) {
            $this->command->warn('Skipping PurchaseSeeder for tenant '.app('currentTenant')->name.': Missing dependency data.');

            return;
        }

        $this->command->info('Seeding Purchase Orders for tenant: '.app('currentTenant')->name);
        $this->command->info("Available: {$suppliers->count()} suppliers, {$users->count()} users, {$products->count()} products, ".count($warehouseIds).' warehouses');

        $productById = $products->keyBy('id');

        // Resolve default tax once: TaxSeeder must run before PurchaseSeeder
        $defaultTax = Tax::where('tenant_id', $tenantId)->where('is_default', true)->first()
            ?? Tax::where('tenant_id', $tenantId)->where('is_active', true)->first();
        $taxId = $defaultTax?->id;

        $created = 0;

        for ($i = 0; $i < 50; $i++) {
            $supplier = $suppliers->random();
            $user = $users->random();

            // PurchaseService takes the order's owner from the authenticated
            // user and db:seed runs with no session, so it has to be set
            // explicitly or every seeded purchase order carries a null user.
            Auth::setUser($user);

            $warehouseId = collect($warehouseIds)->random();
            $orderDate = Carbon::now()->subDays(rand(0, 59));
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Transfer', '-'])->random();
            $status = collect(['Unpaid', 'Paid', 'Partial'])->random();
            $orderDiscount = rand(0, 50);
            $orderDiscountType = collect(['percentage', 'fixed'])->random();

            $invoice = 'PO-'.str_pad(Purchase::where('tenant_id', $tenantId)->count() + 1, 5, '0', STR_PAD_LEFT);

            // The lines are drawn first and the total derived from them,
            // because createPurchase() derives the order total from the very
            // lines it is handed. The old seeder rescaled its own figure when
            // the draw fell outside the wanted range while leaving quantity
            // and price alone, so po_items.total stopped agreeing with
            // quantity * price. Dropping the rescale leaves one number,
            // computed once, by the code that posts it.
            $attempts = 0;
            $maxAttempts = 100;
            $poItemsData = [];
            $totalPurchaseAmount = 0;

            do {
                $poItemsData = [];
                $totalPurchaseAmount = 0;
                $numberOfItems = rand(1, 5);

                for ($j = 0; $j < $numberOfItems; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 10);
                    $price = $product->price;

                    $poItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'discount' => 0,
                        'discountType' => 'fixed',
                        'expiry_date' => $product->has_expiry
                            ? Carbon::now()->addDays(rand(1, 90))->toDateString()
                            : null,
                    ];

                    $totalPurchaseAmount += $quantity * $price;
                }

                $attempts++;
            } while (($totalPurchaseAmount < 100 || $totalPurchaseAmount > 10000) && $attempts < $maxAttempts);

            try {
                $purchase = $this->purchaseService->createPurchase([
                    'invoice' => $invoice,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouseId,
                    'order_date' => $orderDate,
                    'due_date' => $dueDate,
                    'discount_total' => $orderDiscount,
                    'discount_total_type' => $orderDiscountType,
                    'tax_id' => $taxId,
                    'products' => json_encode($poItemsData),
                ]);
            } catch (\Exception $e) {
                $this->command->error("Failed to create purchase {$invoice}: ".$e->getMessage());

                continue;
            }

            $created++;
            $purchase->update(['payment_type' => $paymentType]);

            // createPurchase() books every order Unpaid against accounts
            // payable; the payment is what settles it, and addPayment() books
            // the cash out and refreshes the status the way the app does when
            // the supplier is actually paid.
            if ($status !== 'Unpaid' && $purchase->grand_total > 0) {
                $paidAmount = $status === 'Paid'
                    ? $purchase->grand_total
                    : min(
                        rand(1, max(1, (int) floor($purchase->grand_total * 0.8))),
                        max(1, (int) floor($purchase->grand_total) - 1)
                    );

                try {
                    $this->purchaseService->addPayment($purchase, [
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => $status === 'Paid'
                            ? 'Full payment during seeding.'
                            : 'Partial payment during seeding.',
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Failed to add payment for purchase {$invoice}: ".$e->getMessage());
                }
            }
        }

        $this->command->info('Purchase Orders seeding completed successfully!');
        $this->command->info("Created {$created} purchase orders with items, payments, and journal entries.");
    }
}
