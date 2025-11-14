<?php

namespace Tests\Unit\Services;

use App\Services\PurchaseService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\POItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class PurchaseServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected PurchaseService $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->purchaseService = new PurchaseService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(PurchaseService::class, $this->purchaseService);
    }

    #[Test]
    public function test_create_purchase_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        $data = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'price' => 100,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2026-11-13',
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'price' => 50,
                    'discount' => 5,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2026-12-13',
                ],
            ]),
        ];

        // Act
        $purchase = $this->purchaseService->createPurchase($data);

        // Assert
        $this->assertInstanceOf(Purchase::class, $purchase);
        $this->assertDatabaseHas('po', [
            'id' => $purchase->id,
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'status' => 'Unpaid',
            'total' => 325, // (2*100) + (3*50 - 5*3) - 10 = 200 + 135 - 10 = 325
        ]);

        $this->assertDatabaseHas('po_items', [
            'po_id' => $purchase->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 100,
            'total' => 200,
        ]);

        $this->assertDatabaseHas('po_items', [
            'po_id' => $purchase->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'price' => 50,
            'total' => 135, // (50 - 5) * 3 = 45 * 3 = 135
        ]);

        $this->assertEquals(12, Product::find($product1->id)->stock_quantity); // 10 + 2
        $this->assertEquals(8, Product::find($product2->id)->stock_quantity);  // 5 + 3
    }

    #[Test]
    public function test_update_purchase_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);
        $product3 = Product::factory()->create(['stock_quantity' => 20]);

        // Create an initial purchase
        $initialData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier1->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 5,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'price' => 100,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2026-11-13',
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'price' => 50,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2026-12-13',
                ],
            ]),
        ];
        $initialPurchase = $this->purchaseService->createPurchase($initialData);

        // Expected stock after initial purchase:
        // product1: 10 + 2 = 12
        // product2: 5 + 3 = 8
        $this->assertEquals(12, Product::find($product1->id)->stock_quantity);
        $this->assertEquals(8, Product::find($product2->id)->stock_quantity);

        // Prepare updated data
        $updatedData = [
            'supplier_id' => $supplier2->id,
            'order_date' => '2025-11-15',
            'due_date' => '2026-01-15',
            'discount_total' => 15,
            'discount_total_type' => 'percentage',
            'status' => 'Partial',
            'payment_type' => 'Transfer',
            'products' => json_encode([
                [
                    'product_id' => $product1->id,
                    'quantity' => 1,
                    'price' => 110,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2027-11-13',
                ],
                [
                    'product_id' => $product3->id,
                    'quantity' => 4,
                    'price' => 25,
                    'discount' => 10,
                    'discount_type' => 'percentage',
                    'expiry_date' => '2027-12-13',
                ],
            ]),
        ];

        // Act
        $updatedPurchase = $this->purchaseService->updatePurchase($initialPurchase, $updatedData);

        // Assert
        $this->assertInstanceOf(Purchase::class, $updatedPurchase);
        $this->assertEquals($initialPurchase->id, $updatedPurchase->id);

        // Verify purchase details are updated
        $this->assertDatabaseHas('po', [
            'id' => $updatedPurchase->id,
            'supplier_id' => $supplier2->id,
            'order_date' => '2025-11-15 00:00:00',
            'due_date' => '2026-01-15 00:00:00',
            'discount_total' => 15,
            'discount_total_type' => 'percentage',
            'status' => 'Partial',
            'payment_type' => 'Transfer',
            // Recalculate total:
            // Product1: (1 * 110) = 110
            // Product3: (4 * 25) - (10% of 100) = 100 - 10 = 90
            // Subtotal items: 110 + 90 = 200
            // Order discount: 15% of 200 = 30
            // Final total: 200 - 30 = 170
            'total' => 170,
        ]);

        // Verify old POItems are deleted
        $this->assertDatabaseMissing('po_items', [
            'po_id' => $initialPurchase->id,
            'product_id' => $product2->id,
        ]);

        // Verify new POItems are created
        $this->assertDatabaseHas('po_items', [
            'po_id' => $updatedPurchase->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'price' => 110,
            'total' => 110,
        ]);

        $this->assertDatabaseHas('po_items', [
            'po_id' => $updatedPurchase->id,
            'product_id' => $product3->id,
            'quantity' => 4,
            'price' => 25,
            'discount' => 10,
            'discount_type' => 'percentage',
            'total' => 90, // (25 - 10% of 25) * 4 = (25 - 2.5) * 4 = 22.5 * 4 = 90
        ]);

        // Verify product stock quantities are adjusted
        // Initial: product1: 10, product2: 5, product3: 20
        // After initial purchase: product1: 12, product2: 8, product3: 20
        // After update:
        // product1: 12 (initial) - 2 (old quantity) + 1 (new quantity) = 11
        // product2: 8 (initial) - 3 (old quantity) = 5
        // product3: 20 (initial) + 4 (new quantity) = 24
        $this->assertEquals(11, Product::find($product1->id)->stock_quantity);
        $this->assertEquals(5, Product::find($product2->id)->stock_quantity);
        $this->assertEquals(24, Product::find($product3->id)->stock_quantity);
    }

    #[Test]
    public function test_delete_purchase_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        $initialData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'price' => 100,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2026-11-13',
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'price' => 50,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => '2026-12-13',
                ],
            ]),
        ];
        $purchase = $this->purchaseService->createPurchase($initialData);

        // Expected stock after initial purchase:
        // product1: 10 + 2 = 12
        // product2: 5 + 3 = 8
        $this->assertEquals(12, Product::find($product1->id)->stock_quantity);
        $this->assertEquals(8, Product::find($product2->id)->stock_quantity);

        // Act
        $this->purchaseService->deletePurchase($purchase);

        // Assert
        $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('po_items', ['po_id' => $purchase->id]);

        // Verify product stock quantities are decremented
        $this->assertEquals(10, Product::find($product1->id)->stock_quantity); // 12 - 2 = 10
        $this->assertEquals(5, Product::find($product2->id)->stock_quantity);  // 8 - 3 = 5
    }

    #[Test]
    public function test_bulk_delete_purchases_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);
        $product3 = Product::factory()->create(['stock_quantity' => 20]);

        // Create multiple purchases
        $purchase1 = $this->purchaseService->createPurchase([
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => 1, 'price' => 100, 'discount' => 0, 'discount_type' => 'fixed', 'expiry_date' => '2026-11-13'],
            ]),
        ]);

        $purchase2 = $this->purchaseService->createPurchase([
            'invoice' => 'INV-002',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-14',
            'due_date' => '2025-12-14',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                ['product_id' => $product2->id, 'quantity' => 2, 'price' => 50, 'discount' => 0, 'discount_type' => 'fixed', 'expiry_date' => '2026-12-14'],
            ]),
        ]);

        $purchase3 = $this->purchaseService->createPurchase([
            'invoice' => 'INV-003',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                ['product_id' => $product3->id, 'quantity' => 3, 'price' => 20, 'discount' => 0, 'discount_type' => 'fixed', 'expiry_date' => '2026-11-15'],
            ]),
        ]);

        // Expected stock after initial purchases:
        // product1: 10 + 1 = 11
        // product2: 5 + 2 = 7
        // product3: 20 + 3 = 23
        $this->assertEquals(11, Product::find($product1->id)->stock_quantity);
        $this->assertEquals(7, Product::find($product2->id)->stock_quantity);
        $this->assertEquals(23, Product::find($product3->id)->stock_quantity);

        $idsToDelete = [$purchase1->id, $purchase3->id];

        // Act
        $this->purchaseService->bulkDeletePurchases($idsToDelete);

        // Assert
        $this->assertDatabaseMissing('po', ['id' => $purchase1->id]);
        $this->assertDatabaseMissing('po', ['id' => $purchase3->id]);
        $this->assertDatabaseHas('po', ['id' => $purchase2->id]); // purchase2 should still exist

        $this->assertDatabaseMissing('po_items', ['po_id' => $purchase1->id]);
        $this->assertDatabaseMissing('po_items', ['po_id' => $purchase3->id]);
        $this->assertDatabaseHas('po_items', ['po_id' => $purchase2->id]); // po_items for purchase2 should still exist

        // Verify product stock quantities are decremented for deleted purchases
        $this->assertEquals(10, Product::find($product1->id)->stock_quantity); // 11 - 1 = 10
        $this->assertEquals(7, Product::find($product2->id)->stock_quantity);  // Unchanged
        $this->assertEquals(20, Product::find($product3->id)->stock_quantity); // 23 - 3 = 20
    }

    #[Test]
    public function test_add_payment_and_update_status()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $initialData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                ['product_id' => $product->id, 'quantity' => 5, 'price' => 100, 'discount' => 0, 'discount_type' => 'fixed', 'expiry_date' => '2026-11-13'],
            ]),
        ];
        $purchase = $this->purchaseService->createPurchase($initialData); // Total = 500

        // Assert initial state
        $this->assertEquals('Unpaid', $purchase->status);
        $this->assertEquals(0, $purchase->total_paid);

        // Scenario 1: Partial Payment
        $paymentData1 = [
            'amount' => 200,
            'payment_date' => '2025-11-14',
            'payment_method' => 'Cash',
            'notes' => 'Partial payment 1',
        ];

        // Act
        $payment1 = $this->purchaseService->addPayment($purchase, $paymentData1);

        // Assert payment created
        $this->assertInstanceOf(\App\Models\Payment::class, $payment1);
        $this->assertDatabaseHas('payments', [
            'paymentable_id' => $purchase->id,
            'paymentable_type' => \App\Models\Purchase::class,
            'amount' => 200,
            'payment_method' => 'Cash',
        ]);

        // Refresh purchase model to get updated status and total_paid
        $purchase->refresh();
        $this->assertEquals('Partial', $purchase->status);
        $this->assertEquals(200, $purchase->total_paid);

        // Scenario 2: Full Payment (remaining amount)
        $paymentData2 = [
            'amount' => 300, // Remaining 500 - 200 = 300
            'payment_date' => '2025-11-15',
            'payment_method' => 'Transfer',
            'notes' => 'Final payment',
        ];

        // Act
        $payment2 = $this->purchaseService->addPayment($purchase, $paymentData2);

        // Assert payment created
        $this->assertInstanceOf(\App\Models\Payment::class, $payment2);
        $this->assertDatabaseHas('payments', [
            'paymentable_id' => $purchase->id,
            'paymentable_type' => \App\Models\Purchase::class,
            'amount' => 300,
            'payment_method' => 'Transfer',
        ]);

        // Refresh purchase model
        $purchase->refresh();
        $this->assertEquals('Paid', $purchase->status);
        $this->assertEquals(500, $purchase->total_paid);

        // Scenario 3: Overpayment (should still be Paid)
        $paymentData3 = [
            'amount' => 50,
            'payment_date' => '2025-11-16',
            'payment_method' => 'Card',
            'notes' => 'Overpayment',
        ];

        // Act
        $payment3 = $this->purchaseService->addPayment($purchase, $paymentData3);

        // Refresh purchase model
        $purchase->refresh();
        $this->assertEquals('Paid', $purchase->status);
        $this->assertEquals(550, $purchase->total_paid); // Total paid should reflect overpayment
    }

    #[Test]
    public function test_get_purchase_index_data()
    {
        // Assert - No filters
        $data = $this->purchaseService->getPurchaseIndexData([], 10);
        $this->assertCount(10, $data['pos']);
    }

    #[Test]
    public function test_get_purchase_create_data()
    {
        // Act
        $data = $this->purchaseService->getPurchaseCreateData();

        // Assert
        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('products', $data);

        $this->assertCount(10, $data['pos']);
        $this->assertCount(5, $data['suppliers']);
        $this->assertCount(5, $data['products']);
    }

        #[Test]
    public function test_get_purchase_edit_data()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $supplier = Supplier::first();
        $product1 = Product::find(1);
        $product2 = Product::find(2);

        $purchase = $this->purchaseService->createPurchase([
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => 2, 'price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
                ['product_id' => $product2->id, 'quantity' => 3, 'price' => 50, 'discount' => 5, 'discount_type' => 'fixed'],
            ]),
        ]);

        // Add a payment to make it partial
        $this->purchaseService->addPayment($purchase, [
            'amount' => 100,
            'payment_date' => '2025-11-14',
            'payment_method' => 'Cash',
        ]);
        $purchase->refresh();

        // Act
        $data = $this->purchaseService->getPurchaseEditData($purchase->id);

        // Assert
        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('isPaid', $data);

        $this->assertEquals($purchase->id, $data['pos']->id);
        $this->assertCount(5, $data['suppliers']); // All seeded suppliers
        $this->assertCount(5, $data['products']); // All seeded products
        $this->assertCount(2, $data['pos']->items); // Two items for the purchase
        $this->assertFalse($data['isPaid']); // Status is Partial, not Paid

        // Test with a paid purchase
        $paidPurchase = $this->purchaseService->createPurchase([
            'invoice' => 'INV-002',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => '2025-11-13',
            'due_date' => '2025-12-13',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Unpaid',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => 1, 'price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ]);
        $this->purchaseService->addPayment($paidPurchase, [
            'amount' => 100,
            'payment_date' => '2025-11-14',
            'payment_method' => 'Cash',
        ]);
        $paidPurchase->refresh();

        $dataPaid = $this->purchaseService->getPurchaseEditData($paidPurchase->id);
        $this->assertTrue($dataPaid['isPaid']);
    }
}
