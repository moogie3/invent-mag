<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\SupplierInteraction;
use App\Models\User;
use App\Services\CrmService;
use Tests\Unit\BaseUnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class CrmServiceTest extends BaseUnitTestCase
{
    use CreatesTenant, RefreshDatabase;

    protected CrmService $crmService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->crmService = new CrmService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(CrmService::class, $this->crmService);
    }

    #[Test]
    public function it_can_get_customer_crm_data()
    {
        // Setup: Create a user for interactions
        $user = User::factory()->create();
        Auth::login($user);

        // Setup: Create a customer
        $customer = Customer::factory()->create();

        // Setup: Create categories
        $category1 = Categories::factory()->create(['name' => 'Electronics']);
        $category2 = Categories::factory()->create(['name' => 'Books']);

        // Setup: Create products
        $product1 = Product::factory()->create(['category_id' => $category1->id, 'selling_price' => 100]);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'selling_price' => 50]);
        $product3 = Product::factory()->create(['category_id' => $category1->id, 'selling_price' => 200, 'name' => 'Most Popular Customer Product']);

        // Setup: Create sales for the customer
        $sale1 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now()->subDays(10),
            'total' => 150, // 1*100 + 1*50
        ]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $product1->id, 'quantity' => 1, 'customer_price' => 100, 'total' => 100]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $product2->id, 'quantity' => 1, 'customer_price' => 50, 'total' => 50]);

        $sale2 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now()->subDays(5),
            'total' => 400, // 2*200
        ]);
        SalesItem::factory()->create(['sales_id' => $sale2->id, 'product_id' => $product3->id, 'quantity' => 2, 'customer_price' => 200, 'total' => 400]);

        // Setup: Create customer interactions
        CustomerInteraction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'interaction_date' => now()->subDays(2),
        ]);
        CustomerInteraction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'interaction_date' => now()->subDays(1),
        ]);

        // Call the service method
        $result = $this->crmService->getCustomerCrmData($customer->id, 1);

        // Assertions
        $this->assertArrayHasKey('customer', $result);
        $this->assertEquals($customer->id, $result['customer']->id);
        $this->assertEquals(550, $result['lifetimeValue']); // 150 + 400
        $this->assertEquals(2, $result['totalSalesCount']);
        $this->assertEquals(275, $result['averageOrderValue']); // 550 / 2
        $this->assertEquals('Electronics', $result['favoriteCategory']); // Product1 and Product3 are Electronics
        $this->assertEquals($sale2->created_at->format('Y-m-d'), $result['lastPurchaseDate']->format('Y-m-d'));
        $this->assertEquals(now()->subDays(1)->format('Y-m-d'), $result['lastInteractionDate']->format('Y-m-d'));
        $this->assertEquals($product3->name, $result['mostPurchasedProduct']); // Product3 quantity 2, others 1
        $this->assertEquals(4, $result['totalProductsPurchased']); // 1+1+2
        $this->assertArrayHasKey('sales', $result);
        $this->assertCount(2, $result['sales']);
        $this->assertArrayHasKey('currencySettings', $result);
    }

    #[Test]
    public function it_can_get_supplier_crm_data()
    {
        // Setup: Create a user for interactions
        $user = User::factory()->create();
        Auth::login($user);

        // Setup: Create a supplier
        $supplier = Supplier::factory()->create();

        // Setup: Create categories
        $category1 = Categories::factory()->create(['name' => 'Raw Materials']);
        $category2 = Categories::factory()->create(['name' => 'Components']);

        // Setup: Create products
        $product1 = Product::factory()->create(['category_id' => $category1->id, 'price' => 10]);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'price' => 5]);
        $product3 = Product::factory()->create(['category_id' => $category1->id, 'price' => 20, 'name' => 'Most Purchased Item']);

        // Setup: Create purchases for the supplier
        $purchase1 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->subDays(10),
            'total' => 15, // 1*10 + 1*5
        ]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $product1->id, 'quantity' => 1, 'price' => 10, 'total' => 10]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $product2->id, 'quantity' => 1, 'price' => 5, 'total' => 5]);

        $purchase2 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->subDays(5),
            'total' => 40, // 2*20
        ]);
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $product3->id, 'quantity' => 2, 'price' => 20, 'total' => 40]);

        // Setup: Create supplier interactions
        SupplierInteraction::factory()->create([
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'interaction_date' => now()->subDays(2),
        ]);
        SupplierInteraction::factory()->create([
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'interaction_date' => now()->subDays(1),
        ]);

        // Call the service method
        $result = $this->crmService->getSupplierCrmData($supplier->id, 1);

        // Assertions
        $this->assertArrayHasKey('supplier', $result);
        $this->assertEquals($supplier->id, $result['supplier']->id);
        $this->assertEquals(55, $result['lifetimeValue']); // 15 + 40
        $this->assertEquals(2, $result['totalPurchasesCount']);
        $this->assertEquals(27.5, $result['averagePurchaseValue']); // 55 / 2
        $this->assertEquals('Raw Materials', $result['favoriteCategory']); // Product1 and Product3 are Raw Materials
        $this->assertEquals($purchase2->order_date->format('Y-m-d'), $result['lastPurchaseDate']->format('Y-m-d'));
        $this->assertEquals(now()->subDays(1)->format('Y-m-d'), $result['lastInteractionDate']->format('Y-m-d'));
        $this->assertEquals($product3->name, $result['mostPurchasedProduct']); // Product3 quantity 2, others 1
        $this->assertEquals(4, $result['totalProductsPurchased']); // 1+1+2
        $this->assertArrayHasKey('purchases', $result);
        $this->assertCount(2, $result['purchases']);
        $this->assertArrayHasKey('currencySettings', $result);
    }

    #[Test]
    public function it_can_get_supplier_historical_purchases()
    {
        // Setup: Create a supplier
        $supplier = Supplier::factory()->create();

        // Setup: Create products
        $product1 = Product::factory()->create(['name' => 'Product A', 'price' => 10]);
        $product2 = Product::factory()->create(['name' => 'Product B', 'price' => 20]);

        // Setup: Create purchases for the supplier
        $purchase1 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'invoice' => 'INV-001',
            'order_date' => now()->subDays(30),
            'due_date' => now()->subDays(15),
            'payment_type' => 'Cash',
            'status' => 'Paid',
            'total' => 30,
            'discount_total' => 0,
        ]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $product1->id, 'quantity' => 1, 'price' => 10, 'total' => 10]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $product2->id, 'quantity' => 1, 'price' => 20, 'total' => 20]);

        $purchase2 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'invoice' => 'INV-002',
            'order_date' => now()->subDays(60),
            'due_date' => now()->subDays(45),
            'payment_type' => 'Card',
            'status' => 'Unpaid',
            'total' => 50,
            'discount_total' => 5,
        ]);
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $product1->id, 'quantity' => 3, 'price' => 10, 'total' => 30]);
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $product2->id, 'quantity' => 1, 'price' => 20, 'total' => 20]);

        // Call the service method
        $historicalPurchases = $this->crmService->getSupplierHistoricalPurchases($supplier->id);

        // Assertions
        $this->assertCount(2, $historicalPurchases);

        // Assertions for the most recent purchase (purchase1)
        $this->assertEquals('INV-001', $historicalPurchases[0]['invoice']);
        $this->assertEquals($purchase1->order_date->format('Y-m-d'), $historicalPurchases[0]['order_date']->format('Y-m-d'));
        $this->assertEquals($purchase1->due_date->format('Y-m-d'), $historicalPurchases[0]['due_date']->format('Y-m-d'));
        $this->assertEquals('Cash', $historicalPurchases[0]['payment_method']);
        $this->assertEquals('Paid', $historicalPurchases[0]['status']);
        $this->assertEquals(30, $historicalPurchases[0]['total']);
        $this->assertEquals(0, $historicalPurchases[0]['discount_amount']);
        $this->assertCount(2, $historicalPurchases[0]['items']);
        $this->assertEquals('Product A', $historicalPurchases[0]['items'][0]['product_name']);
        $this->assertEquals(1, $historicalPurchases[0]['items'][0]['quantity']);
        $this->assertEquals(10, $historicalPurchases[0]['items'][0]['price']);

        // Assertions for the older purchase (purchase2)
        $this->assertEquals('INV-002', $historicalPurchases[1]['invoice']);
        $this->assertEquals($purchase2->order_date->format('Y-m-d'), $historicalPurchases[1]['order_date']->format('Y-m-d'));
        $this->assertEquals($purchase2->due_date->format('Y-m-d'), $historicalPurchases[1]['due_date']->format('Y-m-d'));
        $this->assertEquals('Card', $historicalPurchases[1]['payment_method']);
        $this->assertEquals('Unpaid', $historicalPurchases[1]['status']);
        $this->assertEquals(50, $historicalPurchases[1]['total']);
        $this->assertEquals(5, $historicalPurchases[1]['discount_amount']);
        $this->assertCount(2, $historicalPurchases[1]['items']);
        $this->assertEquals('Product A', $historicalPurchases[1]['items'][0]['product_name']);
        $this->assertEquals(3, $historicalPurchases[1]['items'][0]['quantity']);
        $this->assertEquals(10, $historicalPurchases[1]['items'][0]['price']);
    }

    #[Test]
    public function it_can_get_historical_purchases()
    {
        // Setup: Create a customer
        $customer = Customer::factory()->create();

        // Setup: Create products
        $product1 = Product::factory()->create(['name' => 'Product X', 'selling_price' => 100]);
        $product2 = Product::factory()->create(['name' => 'Product Y', 'selling_price' => 50]);

        // Setup: Create sales for the customer
        $sale1 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'invoice' => 'SALE-001',
            'order_date' => now()->subDays(30),
            'total' => 150,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $product1->id, 'quantity' => 1, 'customer_price' => 100, 'total' => 100]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $product2->id, 'quantity' => 1, 'customer_price' => 50, 'total' => 50]);

        $sale2 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'invoice' => 'SALE-002',
            'order_date' => now()->subDays(15),
            'total' => 200,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale2->id, 'product_id' => $product1->id, 'quantity' => 2, 'customer_price' => 100, 'total' => 200]);

        // Call the service method
        $historicalPurchases = $this->crmService->getHistoricalPurchases($customer);

        // Assertions
        $this->assertCount(3, $historicalPurchases); // 2 from sale1, 1 from sale2

        // Assertions for the most recent purchase (from sale2, product1)
        $this->assertEquals('SALE-002', $historicalPurchases[0]['invoice']);
        $this->assertEquals($sale2->order_date->format('Y-m-d'), $historicalPurchases[0]['order_date']->format('Y-m-d'));
        $this->assertEquals($product1->id, $historicalPurchases[0]['product_id']);
        $this->assertEquals('Product X', $historicalPurchases[0]['product_name']);
        $this->assertEquals(2, $historicalPurchases[0]['quantity']);
        $this->assertEquals(100, $historicalPurchases[0]['price_at_purchase']);
        $this->assertEquals(200, $historicalPurchases[0]['line_total']);
        $this->assertEquals(100, $historicalPurchases[0]['customer_latest_price']); // Latest price for Product X

        // Assertions for the next purchase (from sale1, product1)
        $this->assertEquals('SALE-001', $historicalPurchases[1]['invoice']);
        $this->assertEquals($sale1->order_date->format('Y-m-d'), $historicalPurchases[1]['order_date']->format('Y-m-d'));
        $this->assertEquals($product1->id, $historicalPurchases[1]['product_id']);
        $this->assertEquals('Product X', $historicalPurchases[1]['product_name']);
        $this->assertEquals(1, $historicalPurchases[1]['quantity']);
        $this->assertEquals(100, $historicalPurchases[1]['price_at_purchase']);
        $this->assertEquals(100, $historicalPurchases[1]['line_total']);
        $this->assertEquals(100, $historicalPurchases[1]['customer_latest_price']); // Latest price for Product X

        // Assertions for the oldest purchase (from sale1, product2)
        $this->assertEquals('SALE-001', $historicalPurchases[2]['invoice']);
        $this->assertEquals($sale1->order_date->format('Y-m-d'), $historicalPurchases[2]['order_date']->format('Y-m-d'));
        $this->assertEquals($product2->id, $historicalPurchases[2]['product_id']);
        $this->assertEquals('Product Y', $historicalPurchases[2]['product_name']);
        $this->assertEquals(1, $historicalPurchases[2]['quantity']);
        $this->assertEquals(50, $historicalPurchases[2]['price_at_purchase']);
        $this->assertEquals(50, $historicalPurchases[2]['line_total']);
        $this->assertEquals(50, $historicalPurchases[2]['customer_latest_price']); // Latest price for Product Y
    }

    #[Test]
    public function it_can_store_customer_interaction()
    {
        // Setup: Create a customer and a user
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        Auth::login($user); // Authenticate the user

        // Define interaction data
        $data = [
            'type' => 'Call',
            'notes' => 'Discussed new product line.',
            'interaction_date' => now()->format('Y-m-d H:i:s'),
        ];

        // Call the service method
        $interaction = $this->crmService->storeCustomerInteraction($data, $customer->id);

        // Assertions
        $this->assertNotNull($interaction);
        $this->assertEquals($customer->id, $interaction->customer_id);
        $this->assertEquals($user->id, $interaction->user_id);
        $this->assertEquals('Call', $interaction->type);
        $this->assertEquals('Discussed new product line.', $interaction->notes);
        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'Call',
            'notes' => 'Discussed new product line.',
        ]);
    }

    #[Test]
    public function it_can_store_supplier_interaction()
    {
        // Setup: Create a supplier and a user
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create();
        Auth::login($user); // Authenticate the user

        // Define interaction data
        $data = [
            'type' => 'Email',
            'notes' => 'Followed up on order status.',
            'interaction_date' => now()->format('Y-m-d H:i:s'),
        ];

        // Call the service method
        $interaction = $this->crmService->storeSupplierInteraction($data, $supplier->id);

        // Assertions
        $this->assertNotNull($interaction);
        $this->assertEquals($supplier->id, $interaction->supplier_id);
        $this->assertEquals($user->id, $interaction->user_id);
        $this->assertEquals('Email', $interaction->type);
        $this->assertEquals('Followed up on order status.', $interaction->notes);
        $this->assertDatabaseHas('supplier_interactions', [
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'type' => 'Email',
            'notes' => 'Followed up on order status.',
        ]);
    }

    #[Test]
    public function it_can_get_customer_product_history()
    {
        // Setup: Create a customer
        $customer = Customer::factory()->create();

        // Setup: Create products
        $productA = Product::factory()->create(['name' => 'Product A', 'selling_price' => 100]);
        $productB = Product::factory()->create(['name' => 'Product B', 'selling_price' => 50]);

        // Setup: Create sales for the customer
        $sale1 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'invoice' => 'SALE-001',
            'order_date' => now()->subDays(30),
        ]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $productA->id, 'quantity' => 1, 'customer_price' => 100]);
        SalesItem::factory()->create(['sales_id' => $sale1->id, 'product_id' => $productB->id, 'quantity' => 2, 'customer_price' => 50]);

        $sale2 = Sales::factory()->create([
            'customer_id' => $customer->id,
            'invoice' => 'SALE-002',
            'order_date' => now()->subDays(15),
        ]);
        SalesItem::factory()->create(['sales_id' => $sale2->id, 'product_id' => $productA->id, 'quantity' => 3, 'customer_price' => 90]); // Price changed

        // Call the service method
        $productHistory = $this->crmService->getCustomerProductHistory($customer->id);

        // Assertions
        $this->assertCount(2, $productHistory); // Product A and Product B

        // Assertions for Product A
        $this->assertEquals('Product A', $productHistory[0]['product_name']);
        $this->assertEquals(90, $productHistory[0]['last_price']); // Latest price from sale2
        $this->assertCount(2, $productHistory[0]['history']);
        $this->assertEquals('SALE-002', $productHistory[0]['history'][0]['invoice']);
        $this->assertEquals(3, $productHistory[0]['history'][0]['quantity']);
        $this->assertEquals(90, $productHistory[0]['history'][0]['price_at_purchase']);
        $this->assertEquals('SALE-001', $productHistory[0]['history'][1]['invoice']);
        $this->assertEquals(1, $productHistory[0]['history'][1]['quantity']);
        $this->assertEquals(100, $productHistory[0]['history'][1]['price_at_purchase']);

        // Assertions for Product B
        $this->assertEquals('Product B', $productHistory[1]['product_name']);
        $this->assertEquals(50, $productHistory[1]['last_price']); // Only one purchase
        $this->assertCount(1, $productHistory[1]['history']);
        $this->assertEquals('SALE-001', $productHistory[1]['history'][0]['invoice']);
        $this->assertEquals(2, $productHistory[1]['history'][0]['quantity']);
        $this->assertEquals(50, $productHistory[1]['history'][0]['price_at_purchase']);
    }

    #[Test]
    public function it_can_get_supplier_product_history()
    {
        // Setup: Create a supplier
        $supplier = Supplier::factory()->create();

        // Setup: Create products
        $productA = Product::factory()->create(['name' => 'Component A', 'price' => 10]);
        $productB = Product::factory()->create(['name' => 'Component B', 'price' => 20]);

        // Setup: Create purchases for the supplier
        $purchase1 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'invoice' => 'PO-001',
            'order_date' => now()->subDays(30),
        ]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $productA->id, 'quantity' => 5, 'price' => 10]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'product_id' => $productB->id, 'quantity' => 10, 'price' => 20]);

        $purchase2 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'invoice' => 'PO-002',
            'order_date' => now()->subDays(15),
        ]);
        POItem::factory()->create(['po_id' => $purchase2->id, 'product_id' => $productA->id, 'quantity' => 7, 'price' => 12]); // Price changed

        // Call the service method
        $productHistory = $this->crmService->getSupplierProductHistory($supplier->id);

        // Assertions
        $this->assertCount(2, $productHistory); // Product A and Product B

        // Assertions for Product A
        $this->assertEquals('Component A', $productHistory[0]['product_name']);
        $this->assertEquals(12, $productHistory[0]['last_price']); // Latest price from purchase2
        $this->assertCount(2, $productHistory[0]['history']);
        $this->assertEquals('PO-002', $productHistory[0]['history'][0]['invoice']);
        $this->assertEquals(7, $productHistory[0]['history'][0]['quantity']);
        $this->assertEquals(12, $productHistory[0]['history'][0]['price_at_purchase']);
        $this->assertEquals('PO-001', $productHistory[0]['history'][1]['invoice']);
        $this->assertEquals(5, $productHistory[0]['history'][1]['quantity']);
        $this->assertEquals(10, $productHistory[0]['history'][1]['price_at_purchase']);

        // Assertions for Product B
        $this->assertEquals('Component B', $productHistory[1]['product_name']);
        $this->assertEquals(20, $productHistory[1]['last_price']); // Only one purchase
        $this->assertCount(1, $productHistory[1]['history']);
        $this->assertEquals('PO-001', $productHistory[1]['history'][0]['invoice']);
        $this->assertEquals(10, $productHistory[1]['history'][0]['quantity']);
        $this->assertEquals(20, $productHistory[1]['history'][0]['price_at_purchase']);
    }
}