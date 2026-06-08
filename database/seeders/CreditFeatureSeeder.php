<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Models\Payment;
use App\Services\AccountingService;
use App\Services\SalesService;
use App\Services\SalesReturnService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
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

        if (!$customer) {
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
        if (!$user) {
            $this->command->warn('No user found. Please run UserSeeder first.');
            return;
        }

        Auth::setUser($user);

        $warehouse = Warehouse::where('tenant_id', $tenantId)->first();
        if (!$warehouse) {
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

        $this->cleanUpPreviousTestData($customer);

        $product1 = $products[0];
        $product2 = $products[1];

        $this->command->info('');
        $this->command->info('STEP 1: Creating PAID sales invoice with return...');

        $this->ensureStock($product1->id, $warehouse->id, $tenantId, 50);
        $this->ensureStock($product2->id, $warehouse->id, $tenantId, 50);

        $orderDate = Carbon::now()->subDays(10);
        $dueDate = $orderDate->copy()->addDays(30);

        $invoiceNumber = 'INV/CR/TEST/' . $tenantId . '/' . Str::random(12);
        
        $qty1 = 5;
        $price1 = $product1->selling_price;
        $total1 = $qty1 * $price1;
        
        $qty2 = 3;
        $price2 = $product2->selling_price;
        $total2 = $qty2 * $price2;
        
        $subtotal = $total1 + $total2;
        $taxRate = 10;
        $taxAmount = $subtotal * ($taxRate / 100);
        $grandTotal = $subtotal + $taxAmount;

        $salesData = [
            'invoice' => $invoiceNumber,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => $orderDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $grandTotal,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'payment_type' => 'Transfer',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => $qty1, 'customer_price' => $price1, 'discount' => 0, 'discount_type' => 'fixed'],
                ['product_id' => $product2->id, 'quantity' => $qty2, 'customer_price' => $price2, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ];

        $sale = $this->salesService->createSale($salesData);

        $sale->payments()->create([
            'amount' => $grandTotal,
            'payment_date' => $orderDate->copy()->addDays(2),
            'payment_method' => 'Transfer',
            'notes' => 'Full payment for testing credit feature',
            'tenant_id' => $tenantId,
        ]);

        $sale->update(['status' => 'Paid']);

        $this->command->info("Created invoice: {$invoiceNumber}");
        $this->command->info("Status: {$sale->status}");
        $this->command->info("Total: Rp " . number_format($grandTotal, 0, ',', '.'));

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
        $this->command->info("Return amount: Rp " . number_format($returnTotal, 0, ',', '.'));
        $this->command->info("Return status: {$salesReturn->status}");

        $credit = CustomerCredit::where('sales_return_id', $salesReturn->id)->first();
        
        if ($credit) {
            $this->command->info("✓ Customer credit created!");
            $this->command->info("  Credit ID: {$credit->id}");
            $this->command->info("  Credit Amount: Rp " . number_format($credit->amount, 0, ',', '.'));
            $this->command->info("  Credit Status: {$credit->status}");
            $this->command->info("  Customer: {$customer->name}");
        } else {
            $this->command->warn("✗ Credit was NOT created");
            $this->command->warn("  Sale status: {$sale->status}");
            $this->command->warn("  Return status: {$salesReturn->status}");
        }

        $this->command->info('');
        $this->command->info('STEP 2: Creating second PAID invoice with return...');

        $this->ensureStock($product1->id, $warehouse->id, $tenantId, 50);

        $orderDate2 = Carbon::now()->subDays(3);
        $invoiceNumber2 = 'INV/CR/TEST/' . $tenantId . '/' . Str::random(12);
        
        $qty3 = 4;
        $price3 = $product1->selling_price;
        $total3 = $qty3 * $price3;
        
        $subtotal2 = $total3;
        $taxAmount2 = $subtotal2 * ($taxRate / 100);
        $grandTotal2 = $subtotal2 + $taxAmount2;

        $salesData2 = [
            'invoice' => $invoiceNumber2,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => $orderDate2->toDateString(),
            'due_date' => $orderDate2->copy()->addDays(30)->toDateString(),
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount2,
            'total' => $grandTotal2,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'payment_type' => 'Transfer',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => $qty3, 'customer_price' => $price3, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ];

        $sale2 = $this->salesService->createSale($salesData2);

        $sale2->payments()->create([
            'amount' => $grandTotal2,
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
        $this->command->info("Second credit amount: Rp " . number_format($returnTotal2, 0, ',', '.'));

        $this->command->info('');
        $this->command->info('STEP 3: Creating order using customer credit...');

        $allCredits = CustomerCredit::where('customer_id', $customer->id)->where('status', 'active')->get();
        $totalAvailable = $allCredits->sum('available_amount');

        $this->command->info("Total available credits: Rp " . number_format($totalAvailable, 0, ',', '.'));

        $orderDate3 = Carbon::now();
        $invoiceNumber3 = 'INV/CR/TEST/' . $tenantId . '/' . Str::random(12);
        
        $creditToUse = min($totalAvailable, 50000); // Use credit up to 50k or available amount
        
        $qty4 = 1;
        $price4 = $product2->selling_price;
        $total4 = $qty4 * $price4;
        
        $subtotal3 = $total4;
        $taxAmount3 = $subtotal3 * ($taxRate / 100);
        $grandTotal3 = $subtotal3 + $taxAmount3;

        $this->command->info("Order subtotal: Rp " . number_format($grandTotal3, 0, ',', '.'));
        $this->command->info("Credit to apply: Rp " . number_format($creditToUse, 0, ',', '.'));

        $this->ensureStock($product2->id, $warehouse->id, $tenantId, 50);

        $salesData3 = [
            'invoice' => $invoiceNumber3,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => $orderDate3->toDateString(),
            'due_date' => $orderDate3->copy()->addDays(30)->toDateString(),
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount3,
            'total' => $grandTotal3,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'payment_type' => 'Transfer',
            'customer_credit_amount' => $creditToUse,
            'products' => json_encode([
                ['product_id' => $product2->id, 'quantity' => $qty4, 'customer_price' => $price4, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ];

        $sale3 = $this->salesService->createSale($salesData3);

        $sale3->refresh();

        $this->command->info("Created invoice: {$invoiceNumber3}");
        $this->command->info("Credit applied: Rp " . number_format($sale3->customer_credit_used, 0, ',', '.'));
        $this->command->info("Status: {$sale3->status}");
        $this->command->info("Balance: Rp " . number_format($sale3->balance, 0, ',', '.'));

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('CREDIT FEATURE SEEDING COMPLETE');
        $this->command->info('===========================================');
        $this->command->info('');
        $this->command->info('Customer: ' . $customer->name);
        $this->command->info('Total Credits Created: ' . CustomerCredit::where('customer_id', $customer->id)->count());
        $this->command->info('Active Credits: ' . CustomerCredit::where('customer_id', $customer->id)->where('status', 'active')->count());
        $this->command->info('Total Available: Rp ' . number_format(CustomerCredit::where('customer_id', $customer->id)->sum(\DB::raw('amount - used_amount')), 0, ',', '.'));
        $this->command->info('');
        $this->command->info('To test:');
        $this->command->info('1. Go to Sales Order - should see credit options');
        $this->command->info('2. Select customer ' . $customer->name);
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
            Sales::whereIn('id', $testInvoices)->delete();
        }

        $testReturns = SalesReturn::whereHas('sale', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->pluck('id');

        if ($testReturns->isNotEmpty()) {
            SalesReturnItem::whereIn('sales_return_id', $testReturns)->delete();
            SalesReturn::whereIn('id', $testReturns)->delete();
        }

        CustomerCredit::where('customer_id', $customer->id)->delete();

        $this->command->info('Cleaned up previous test data for customer: ' . $customer->name);
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
