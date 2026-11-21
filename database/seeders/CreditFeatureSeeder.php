<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreditFeatureSeeder extends Seeder
{
    protected $accountingService;

    protected $salesService;

    protected $salesReturnService;

    public function __construct(
        AccountingService $accountingService,
        SalesService $salesService,
        SalesReturnService $salesReturnService
    ) {
        $this->accountingService = $accountingService;
        $this->salesService = $salesService;
        $this->salesReturnService = $salesReturnService;
    }

    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $this->command->info('===========================================');
        $this->command->info('Seeding Credit Feature Test Data');
        $this->command->info('===========================================');

        $customer = Customer::where('tenant_id', $tenantId)
            ->where('email', 'budi.santoso@example.com')
            ->first();

        if (! $customer) {
            $customer = Customer::create([
                'name' => 'Toko Maju Jaya',
                'email' => 'majujaya@example.com',
                'phone_number' => '081234567890',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'payment_terms' => 30,
                'tenant_id' => $tenantId,
            ]);
            $this->command->info("Created new customer: {$customer->name}");
        } else {
            $this->command->info("Using existing customer: {$customer->name}");
        }

        $user = User::where('tenant_id', $tenantId)->first();
        if (! $user) {
            $this->command->warn('No user found. Please run UserSeeder first.');

            return;
        }

        Auth::setUser($user);

        $warehouse = Warehouse::where('tenant_id', $tenantId)->first();
        if (! $warehouse) {
            $this->command->warn('No warehouse found. Please run WarehouseSeeder first.');

            return;
        }

        $products = Product::where('tenant_id', $tenantId)->limit(5)->get();
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');

            return;
        }

        $this->command->info("Using warehouse: {$warehouse->name}");
        $this->command->info("Available products: {$products->count()}");

        // The three invoices this seeder writes are tagged INV/CR/TEST, so
        // their presence means a previous run already did this job. Skipping
        // matters because cleanUpPreviousTestData() below deletes the sales,
        // returns and credits it finds but leaves their journal entries
        // behind: a second run therefore deleted the documents, kept the GL
        // rows pointing at them, then booked a fresh set. The ledger ended up
        // carrying both the orphaned entries and the new ones.
        $alreadySeeded = Sales::where('customer_id', $customer->id)
            ->where('invoice', 'like', '%INV/CR/TEST/%')
            ->exists();

        if ($alreadySeeded) {
            $this->command->info('Credit feature test data already seeded for tenant '.$tenantId.', skipping.');

            return;
        }

        $this->cleanUpPreviousTestData($customer);

        $product1 = $products[0];
        $product2 = $products[1];

        $this->command->info('');
        $this->command->info('STEP 1: Creating PAID sales invoice with return...');

        $this->ensureStock($product1->id, $warehouse->id, $tenantId, 50);
        $this->ensureStock($product2->id, $warehouse->id, $tenantId, 50);

        $orderDate = Carbon::now()->subDays(10);
        $dueDate = $orderDate->copy()->addDays(30);

        $invoiceNumber = 'INV/CR/TEST/'.$tenantId.'/'.Str::random(12);

        $qty1 = 5;
        $price1 = $product1->selling_price;

        $qty2 = 3;
        $price2 = $product2->selling_price;

        // No tax rate, tax amount or total is sent here: createSale()
        // recomputes all three from the tax record and the line items, so a
        // second set of figures in this array was never read. It was worse
        // than dead, though, because the payment below was made against THOSE
        // numbers instead of the invoice's real total, and every seeded
        // invoice ended up over- or under-paid while sitting at Paid.
        $salesData = [
            'invoice' => $invoiceNumber,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => $orderDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => $qty1, 'customer_price' => $price1, 'discount' => 0, 'discount_type' => 'fixed'],
                ['product_id' => $product2->id, 'quantity' => $qty2, 'customer_price' => $price2, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ];

        $sale = $this->salesService->createSale($salesData);

        // Exactly what the invoice owes, read back from the document the
        // service just wrote.
        $saleTotal = (float) $sale->fresh()->total;

        $sale->payments()->create([
            'amount' => $saleTotal,
            'payment_date' => $orderDate->copy()->addDays(2),
            'payment_method' => 'Transfer',
            'notes' => 'Full payment for testing credit feature',
            'tenant_id' => $tenantId,
        ]);

        $sale->update(['status' => 'Paid']);

        $this->command->info("Created invoice: {$invoiceNumber}");
        $this->command->info("Status: {$sale->status}");
        $this->command->info('Total: Rp '.number_format($saleTotal, 0, ',', '.'));

        $returnDate = Carbon::now()->subDays(5);
        $returnQty = 3;
        $returnTotal = $returnQty * $price1;

        $returnItemsJson = json_encode([
            ['product_id' => $product1->id, 'returned_quantity' => $returnQty, 'price' => $price1, 'total' => $returnTotal],
        ]);

        $returnData = [
            'sales_id' => $sale->id,
            'return_date' => $returnDate->toDateString(),
            'reason' => 'Customer requested return - damaged items',
            'status' => 'Completed',
            'items' => $returnItemsJson,
        ];

        $salesReturn = $this->salesReturnService->createSalesReturn($returnData);

        $this->command->info("Created return #{$salesReturn->id}");
        $this->command->info('Return amount: Rp '.number_format($returnTotal, 0, ',', '.'));
        $this->command->info("Return status: {$salesReturn->status}");

        $credit = CustomerCredit::where('sales_return_id', $salesReturn->id)->first();

        if ($credit) {
            $this->command->info('✓ Customer credit created!');
            $this->command->info("  Credit ID: {$credit->id}");
            $this->command->info('  Credit Amount: Rp '.number_format($credit->amount, 0, ',', '.'));
            $this->command->info("  Credit Status: {$credit->status}");
            $this->command->info("  Customer: {$customer->name}");
        } else {
            $this->command->warn('✗ Credit was NOT created');
            $this->command->warn("  Sale status: {$sale->status}");
            $this->command->warn("  Return status: {$salesReturn->status}");
        }

        $this->command->info('');
        $this->command->info('STEP 2: Creating second PAID invoice with return...');

        $this->ensureStock($product1->id, $warehouse->id, $tenantId, 50);

        $orderDate2 = Carbon::now()->subDays(3);
        $invoiceNumber2 = 'INV/CR/TEST/'.$tenantId.'/'.Str::random(12);

        $qty3 = 4;
        $price3 = $product1->selling_price;

        // Same as step 1: the service owns the arithmetic, so only the line
        // items are handed over and the payment is sized from the invoice.
        $salesData2 = [
            'invoice' => $invoiceNumber2,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => $orderDate2->toDateString(),
            'due_date' => $orderDate2->copy()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => $qty3, 'customer_price' => $price3, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ];

        $sale2 = $this->salesService->createSale($salesData2);

        $sale2Total = (float) $sale2->fresh()->total;

        $sale2->payments()->create([
            'amount' => $sale2Total,
            'payment_date' => $orderDate2->copy()->addDays(1),
            'payment_method' => 'Transfer',
            'notes' => 'Full payment for second invoice',
            'tenant_id' => $tenantId,
        ]);
        $sale2->update(['status' => 'Paid']);

        $returnDate2 = Carbon::now()->subDays(1);
        $returnQty2 = 4;
        $returnTotal2 = $returnQty2 * $price3;

        $returnData2 = [
            'sales_id' => $sale2->id,
            'return_date' => $returnDate2->toDateString(),
            'reason' => 'Wrong item delivered',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $product1->id, 'returned_quantity' => $returnQty2, 'price' => $price3, 'total' => $returnTotal2],
            ]),
        ];

        $salesReturn2 = $this->salesReturnService->createSalesReturn($returnData2);

        $this->command->info("Created second return #{$salesReturn2->id}");
        $this->command->info('Second credit amount: Rp '.number_format($returnTotal2, 0, ',', '.'));

        $this->command->info('');
        $this->command->info('STEP 3: Creating order using customer credit...');

        $allCredits = CustomerCredit::where('customer_id', $customer->id)->where('status', 'active')->get();
        $totalAvailable = $allCredits->sum('available_amount');

        $this->command->info('Total available credits: Rp '.number_format($totalAvailable, 0, ',', '.'));

        $orderDate3 = Carbon::now();
        $invoiceNumber3 = 'INV/CR/TEST/'.$tenantId.'/'.Str::random(12);

        $qty4 = 1;
        $price4 = $product2->selling_price;

        // The service caps the credit at what this invoice still owes, so the
        // seeder only states how much it is willing to use and reads the real
        // figure back afterwards. Guessing the invoice total here to build the
        // cap is what put a number the service had already recomputed next to
        // it: the credit would then be sized against a total that did not
        // exist, and anything above the invoice's real total would consume
        // credit for nothing and mark the invoice Paid without cash moving.
        $creditToUse = min($totalAvailable, 50000);

        $this->command->info('Credit offered: Rp '.number_format($creditToUse, 0, ',', '.'));

        $this->ensureStock($product2->id, $warehouse->id, $tenantId, 50);

        $salesData3 = [
            'invoice' => $invoiceNumber3,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => $orderDate3->toDateString(),
            'due_date' => $orderDate3->copy()->addDays(30)->toDateString(),
            'customer_credit_amount' => $creditToUse,
            'products' => json_encode([
                ['product_id' => $product2->id, 'quantity' => $qty4, 'customer_price' => $price4, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ];

        $sale3 = $this->salesService->createSale($salesData3);

        $sale3->refresh();

        $this->command->info("Created invoice: {$invoiceNumber3}");
        $this->command->info('Credit applied: Rp '.number_format($sale3->customer_credit_used, 0, ',', '.'));
        $this->command->info("Status: {$sale3->status}");
        $this->command->info('Balance: Rp '.number_format($sale3->balance, 0, ',', '.'));

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('CREDIT FEATURE SEEDING COMPLETE');
        $this->command->info('===========================================');
        $this->command->info('');
        $this->command->info('Customer: '.$customer->name);
        $this->command->info('Total Credits Created: '.CustomerCredit::where('customer_id', $customer->id)->count());
        $this->command->info('Active Credits: '.CustomerCredit::where('customer_id', $customer->id)->where('status', 'active')->count());
        $this->command->info('Total Available: Rp '.number_format(CustomerCredit::where('customer_id', $customer->id)->sum(\DB::raw('amount - used_amount')), 0, ',', '.'));
        $this->command->info('');
        $this->command->info('To test:');
        $this->command->info('1. Go to Sales Order - should see credit options');
        $this->command->info('2. Select customer '.$customer->name);
        $this->command->info('3. Credit section will show available credits');
        $this->command->info('4. View customer CRM - should show Customer Credits tab');
    }

    protected function cleanUpPreviousTestData($customer)
    {
        $tenantId = app('currentTenant')->id;

        $testInvoices = Sales::where('customer_id', $customer->id)
            ->where('invoice', 'like', '%INV/CR/TEST/%')
            ->pluck('id');

        if ($testInvoices->isNotEmpty()) {
            SalesItem::whereIn('sales_id', $testInvoices)->delete();
            Payment::whereIn('paymentable_id', $testInvoices)
                ->where('paymentable_type', 'App\Models\Sales')
                ->delete();
            // The journal entries have to go with the documents they came
            // from. Deleting the sales and leaving these behind orphaned GL
            // rows against invoices that no longer exist, and the transactions
            // follow their journal entry through the cascade.
            JournalEntry::where('sourceable_type', 'App\Models\Sales')
                ->whereIn('sourceable_id', $testInvoices)
                ->delete();
            Sales::whereIn('id', $testInvoices)->delete();
        }

        $testReturns = SalesReturn::whereHas('sale', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->pluck('id');

        if ($testReturns->isNotEmpty()) {
            JournalEntry::where('sourceable_type', 'App\Models\SalesReturn')
                ->whereIn('sourceable_id', $testReturns)
                ->delete();
            SalesReturnItem::whereIn('sales_return_id', $testReturns)->delete();
            SalesReturn::whereIn('id', $testReturns)->delete();
        }

        CustomerCredit::where('customer_id', $customer->id)->delete();

        $this->command->info('Cleaned up previous test data for customer: '.$customer->name);
    }

    protected function ensureStock($productId, $warehouseId, $tenantId, $minimum)
    {
        $stock = ProductWarehouse::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->first();

        $current = $stock ? $stock->quantity : 0;
        if ($current < $minimum) {
            $additional = $minimum - $current;
            if ($stock) {
                $stock->increment('quantity', $additional);
            } else {
                ProductWarehouse::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $additional,
                    'tenant_id' => $tenantId,
                ]);
            }
        }
    }
}
