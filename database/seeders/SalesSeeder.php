<?php

namespace Database\Seeders;

use App\Helpers\SalesHelper;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class SalesSeeder extends Seeder
{
    public function __construct(protected SalesService $salesService) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $existingSales = Sales::where('tenant_id', $tenantId)->count();
        if ($existingSales > 0) {
            $this->command->info('Sales already exist for tenant '.app('currentTenant')->name.'. Skipping to preserve existing data.');

            return;
        }

        $customers = Customer::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->get();
        $products = Product::where('tenant_id', $tenantId)->get();

        $warehouseIds = Warehouse::where('tenant_id', $tenantId)->pluck('id')->toArray();

        if ($customers->isEmpty() || $users->isEmpty() || $products->isEmpty() || empty($warehouseIds)) {
            $this->command->warn('Skipping SalesSeeder for tenant '.app('currentTenant')->name.': Missing dependency data.');

            return;
        }

        $this->command->info('Seeding Sales data for tenant: '.app('currentTenant')->name);
        $this->command->info("Available: {$customers->count()} customers, {$users->count()} users, {$products->count()} products, ".count($warehouseIds).' warehouses');

        $productById = $products->keyBy('id');

        // Resolve default tax once: TaxSeeder must run before SalesSeeder
        $defaultTax = Tax::where('tenant_id', $tenantId)->where('is_default', true)->first()
            ?? Tax::where('tenant_id', $tenantId)->where('is_active', true)->first();
        $taxId = $defaultTax?->id;
        $taxRate = $defaultTax ? (float) $defaultTax->rate : 0.0;
        $taxType = $defaultTax ? $defaultTax->type : 'none';

        $created = 0;

        for ($i = 0; $i < 50; $i++) { // Create 50 sample sales
            $customer = $customers->random();
            $user = $users->random();

            // SalesService takes the invoice's owner from the authenticated
            // user and db:seed runs with no session, so the randomly chosen
            // user has to be set explicitly or every seeded sale would be
            // written with a null user_id.
            Auth::setUser($user);

            $warehouseId = collect($warehouseIds)->random();
            $orderDate = Carbon::now()->subDays(rand(0, 59));
            $dueDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentType = collect(['Cash', 'Card', 'Transfer', 'eWallet', '-'])->random();
            $status = collect(['Unpaid', 'Paid', 'Partial'])->random();
            $orderDiscount = rand(0, 20);
            $orderDiscountType = collect(['percentage', 'fixed'])->random();

            $invoice = 'INV-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT).'-'.$tenantId;

            // The lines are drawn first and the totals derived from them,
            // because createSale() derives the invoice total from the very
            // lines it is handed. The old seeder computed a total of its own
            // and, when the draw fell outside the wanted range, rescaled that
            // figure without touching the lines, so the stored invoice total
            // and the sum of its items disagreed. Dropping the rescale leaves
            // one number, computed once, by the code that posts it.
            $attempts = 0;
            $maxAttempts = 100;
            $salesItemsData = [];
            $netSales = 0;
            $totalCostOfGoods = 0;
            $grandTotal = 0;

            do {
                $salesItemsData = [];
                $numberOfItems = rand(1, 5);

                for ($j = 0; $j < $numberOfItems; $j++) {
                    $product = $products->random();
                    $salesItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => rand(1, 5),
                        'customer_price' => $product->selling_price,
                    ];
                }

                $subTotal = 0;
                $totalCostOfGoods = 0;
                foreach ($salesItemsData as $line) {
                    $subTotal += $line['quantity'] * $line['customer_price'];
                    $totalCostOfGoods += $line['quantity'] * $productById[$line['product_id']]->price;
                }

                $discountAmount = SalesHelper::calculateDiscount($subTotal, $orderDiscount, $orderDiscountType);
                $netSales = $subTotal - $discountAmount;
                $taxAmount = SalesHelper::calculateTaxAmount($netSales, $taxRate, $taxType);
                $grandTotal = ($taxType === 'inclusive') ? $netSales : ($netSales + $taxAmount);

                $attempts++;
            } while (($grandTotal < 100 || $grandTotal > 10000 || $netSales < $totalCostOfGoods) && $attempts < $maxAttempts);

            // createSale() refuses to sell stock a warehouse does not hold,
            // and the purchase orders that stocked these warehouses pick their
            // own warehouse at random, so the record is topped up before the
            // lines are handed over rather than after the sale exists.
            foreach (collect($salesItemsData)->groupBy('product_id') as $productId => $lines) {
                $stockRecord = ProductWarehouse::firstOrCreate(
                    [
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'tenant_id' => $tenantId,
                    ],
                    ['quantity' => 0]
                );

                $needed = collect($lines)->sum('quantity');
                if ($stockRecord->quantity < $needed) {
                    $stockRecord->update(['quantity' => $stockRecord->quantity + 100]);
                }
            }

            try {
                $sale = $this->salesService->createSale([
                    'invoice' => $invoice,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouseId,
                    'order_date' => $orderDate,
                    'due_date' => $dueDate,
                    'discount_total' => $orderDiscount,
                    'discount_total_type' => $orderDiscountType,
                    'tax_id' => $taxId,
                    'products' => json_encode($salesItemsData),
                ]);
            } catch (\Exception $e) {
                $this->command->error("Failed to create sale {$invoice}: ".$e->getMessage());

                continue;
            }

            $created++;
            $sale->update(['payment_type' => $paymentType]);

            // createSale() books every invoice Unpaid; the payment is what
            // moves it to Paid or Partial, and addPayment() books the cash
            // against AR and refreshes the status the way the app does when
            // someone actually takes the money.
            if ($status !== 'Unpaid' && $sale->grand_total > 0) {
                $paidAmount = $status === 'Paid'
                    ? $sale->grand_total
                    : min(
                        rand(1, max(1, (int) floor($sale->grand_total * 0.8))),
                        max(1, (int) floor($sale->grand_total) - 1)
                    );

                try {
                    $this->salesService->addPayment($sale, [
                        'amount' => $paidAmount,
                        'payment_date' => $orderDate->copy()->addDays(rand(0, 5)),
                        'payment_method' => $paymentType,
                        'notes' => $status === 'Paid'
                            ? 'Full payment during seeding.'
                            : 'Partial payment during seeding.',
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Failed to add payment for sale {$invoice}: ".$e->getMessage());
                }
            }
        }

        $this->command->info('Sales seeding completed successfully!');
        $this->command->info("Created {$created} sales invoices with items, payments, and journal entries.");
    }
}
