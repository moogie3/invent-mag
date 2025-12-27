<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\PosService;
use Tests\Unit\BaseUnitTestCase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;

class PosServiceTest extends BaseUnitTestCase
{
    protected PosService $posService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->posService = new PosService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Product::truncate();
        Customer::truncate();
        Categories::truncate();
        Unit::truncate();
        Supplier::truncate();
        Sales::truncate();
        SalesItem::truncate();
        User::truncate();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(PosService::class, $this->posService);
    }

    #[Test]
    public function it_can_get_pos_index_data()
    {
        // 1. Setup data
        $categories = Categories::factory()->count(2)->create();
        $units = Unit::factory()->count(2)->create();
        $suppliers = Supplier::factory()->count(2)->create();

        Product::factory()->count(5)->create([
            'category_id' => $categories->first()->id,
            'units_id' => $units->first()->id,
            'supplier_id' => $suppliers->first()->id,
        ]);
        Customer::factory()->count(3)->create();
        Customer::factory()->create(['name' => 'Walk In Customer']);

        // 2. Call the service method
        $data = $this->posService->getPosIndexData();

        // 3. Assertions
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertArrayHasKey('walkInCustomerId', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('units', $data);
        $this->assertArrayHasKey('suppliers', $data);

        $this->assertCount(5, $data['products']);
        $this->assertCount(4, $data['customers']);
        $this->assertNotNull($data['walkInCustomerId']);
        $this->assertCount(2, $data['categories']);
        $this->assertCount(2, $data['units']);
        $this->assertCount(2, $data['suppliers']);
    }

    #[Test]
    public function it_can_create_a_sale()
    {
        // 1. Setup data
        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product1 = Product::factory()->create(['selling_price' => 100, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['selling_price' => 50, 'stock_quantity' => 20]);

        $productsPayload = json_encode([
            ['id' => $product1->id, 'quantity' => 2, 'price' => 100],
            ['id' => $product2->id, 'quantity' => 3, 'price' => 50],
        ]);

        $data = [
            'products' => $productsPayload,
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'tax_rate' => 10,
            'amount_received' => 400,
            'change_amount' => 15,
        ];

        // 2. Call the service method
        $sale = $this->posService->createSale($data);

        // 3. Assertions
        $this->assertInstanceOf(Sales::class, $sale);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'customer_id' => $customer->id,
            'status' => 'Paid',
            'is_pos' => true,
        ]);

        // Assert totals
        $this->assertEquals(10, $sale->order_discount);
        $this->assertEquals(34, $sale->total_tax); // (350 - 10) * 0.10 = 34
        $this->assertEquals(374, $sale->total); // 350 - 10 + 34 = 374
        
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $product1->id, 'quantity' => 2]);
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $product2->id, 'quantity' => 3]);

        $this->assertDatabaseHas('payments', ['paymentable_id' => $sale->id, 'paymentable_type' => Sales::class]);

        $this->assertEquals(8, $product1->fresh()->stock_quantity);
        $this->assertEquals(17, $product2->fresh()->stock_quantity);
    }

    #[Test]
    public function it_can_get_receipt_data()
    {
        // 1. Setup data
        $sale = Sales::factory()->create([
            'order_discount' => 10,
            'order_discount_type' => 'fixed',
            'tax_rate' => 10,
            'amount_received' => 400,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'customer_price' => 100, 'quantity' => 2, 'discount' => 0]); // 200
        SalesItem::factory()->create(['sales_id' => $sale->id, 'customer_price' => 50, 'quantity' => 3, 'discount' => 0]);  // 150
        
        // Manually set total because factory creates random total
        $sale->total = 350;
        $sale->save();


        // 2. Call the service method
        $data = $this->posService->getReceiptData($sale);

        // 3. Assertions
        $this->assertEquals(350, $data['subTotal']);
        $this->assertEquals(10, $data['orderDiscountAmount']);
        $this->assertEquals(10, $data['taxRate']);
        $this->assertEquals(34, $data['taxAmount']); // (350 - 10) * 0.10 = 34
        $this->assertEquals(374, $data['grandTotal']); // 350 - 10 + 34 = 374
        $this->assertEquals(400, $data['amountReceived']);
        $this->assertEquals(26, $data['change']); // 400 - 374 = 26
    }
}
