<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Tests\Unit\BaseUnitTestCase;
use Illuminate\Support\Facades\Auth;

class PurchaseServiceTest extends BaseUnitTestCase
{
    protected PurchaseService $purchaseService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseService = new PurchaseService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_get_purchase_index_data()
    {
        Supplier::factory()->count(2)->create(['location' => 'IN']);
        Supplier::factory()->count(2)->create(['location' => 'OUT']);
        Purchase::factory()->count(5)->create();

        $data = $this->purchaseService->getPurchaseIndexData([], 10);

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('inCount', $data);
        $this->assertArrayHasKey('outCount', $data);
        $this->assertArrayHasKey('inCountamount', $data);
        $this->assertArrayHasKey('outCountamount', $data);
        $this->assertArrayHasKey('totalinvoice', $data);
        $this->assertArrayHasKey('totalMonthly', $data);
        $this->assertArrayHasKey('paymentMonthly', $data);
    }

    public function test_get_purchase_create_data()
    {
        $data = $this->purchaseService->getPurchaseCreateData();

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('products', $data);
    }

    public function test_get_purchase_edit_data()
    {
        $purchase = Purchase::factory()->create();
        $data = $this->purchaseService->getPurchaseEditData($purchase->id);

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('isPaid', $data);
    }

    public function test_get_purchase_view_data()
    {
        $purchase = Purchase::factory()->hasItems(2)->create();
        $data = $this->purchaseService->getPurchaseViewData($purchase->id);

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('itemCount', $data);
        $this->assertArrayHasKey('subtotal', $data);
        $this->assertArrayHasKey('orderDiscount', $data);
        $this->assertArrayHasKey('finalTotal', $data);
        $this->assertArrayHasKey('totalProductDiscount', $data);
    }

    public function test_get_purchase_for_modal()
    {
        $purchase = Purchase::factory()->hasItems(2)->create();
        $result = $this->purchaseService->getPurchaseForModal($purchase->id);

        $this->assertEquals($purchase->id, $result->id);
    }

    public function test_create_purchase()
    {
        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        $purchaseData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => 2, 'price' => 100],
                ['product_id' => $product2->id, 'quantity' => 3, 'price' => 50],
            ]),
        ];

        $purchase = $this->purchaseService->createPurchase($purchaseData);

        $this->assertDatabaseHas('po', ['invoice' => 'INV-001']);
        $this->assertDatabaseHas('po_items', ['po_id' => $purchase->id, 'product_id' => $product1->id]);
        $this->assertEquals(12, Product::find($product1->id)->stock_quantity);
        $this->assertEquals(8, Product::find($product2->id)->stock_quantity);
    }

    public function test_update_purchase()
    {
        $purchase = Purchase::factory()->hasItems(1)->create();
        $oldItem = $purchase->items->first();
        $product = Product::find($oldItem->product_id);
        $initialStock = $product->stock_quantity;

        $supplier = Supplier::factory()->create();
        $newProduct = Product::factory()->create(['stock_quantity' => 20]);

        $updateData = [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(60)->toDateString(),
            'discount_total' => 20,
            'discount_total_type' => 'percentage',
            'status' => 'Paid',
            'payment_type' => 'Cash',
            'products' => json_encode([
                ['product_id' => $newProduct->id, 'quantity' => 5, 'price' => 200],
            ]),
        ];

        $this->purchaseService->updatePurchase($purchase, $updateData);

        $this->assertDatabaseHas('po', ['id' => $purchase->id, 'status' => 'Paid']);
        $this->assertDatabaseMissing('po_items', ['id' => $oldItem->id]);
        $this->assertDatabaseHas('po_items', ['po_id' => $purchase->id, 'product_id' => $newProduct->id]);
        $this->assertEquals($initialStock - $oldItem->quantity, Product::find($oldItem->product_id)->stock_quantity);
        $this->assertEquals(25, Product::find($newProduct->id)->stock_quantity);
    }

    public function test_add_payment()
    {
        $purchase = Purchase::factory()->hasItems(1, ['price' => 1000, 'quantity' => 1])->create();
        $paymentData = [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ];

        $this->purchaseService->addPayment($purchase, $paymentData);
        $purchase->refresh(); // Refresh the purchase model after status update
        $this->assertDatabaseHas('payments', ['paymentable_id' => $purchase->id, 'amount' => 500]);
        $this->assertEquals('Partial', $purchase->status);
    }

    public function test_update_purchase_status()
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $purchaseData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 1000],
            ]),
        ];
        $purchase = $this->purchaseService->createPurchase($purchaseData);
        $purchase->refresh(); // Ensure all calculated fields are fresh

        // Test Partial
        $purchase->payments()->create(['amount' => 500, 'payment_date' => now()->toDateString(), 'payment_method' => 'Cash']);
        $purchase->load('payments'); // Reload payments relationship
        $this->purchaseService->updatePurchaseStatus($purchase);
        $purchase->refresh();
        $this->assertEquals('Partial', $purchase->status);

        // Test Paid
        $purchase->payments()->create(['amount' => 500, 'payment_date' => now()->toDateString(), 'payment_method' => 'Cash']);
        $purchase->load('payments'); // Reload payments relationship
        $this->purchaseService->updatePurchaseStatus($purchase);
        $purchase->refresh();
        $this->assertEquals('Paid', $purchase->status);

        // Test Unpaid
        $purchase->payments()->delete();
        $purchase->load('payments'); // Reload payments relationship
        $this->purchaseService->updatePurchaseStatus($purchase);
        $purchase->refresh();
        $this->assertEquals('Unpaid', $purchase->status);
    }

    public function test_delete_purchase()
    {
        $purchase = Purchase::factory()->hasItems(1)->create();
        $item = $purchase->items->first();
        $product = Product::find($item->product_id);
        $initialStock = $product->stock_quantity;

        $this->purchaseService->deletePurchase($purchase);

        $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('po_items', ['id' => $item->id]);
        $this->assertEquals($initialStock - $item->quantity, Product::find($item->product_id)->stock_quantity);
    }

    public function test_bulk_delete_purchases()
    {
        $purchases = Purchase::factory()->count(3)->hasItems(1)->create();
        $ids = $purchases->pluck('id')->toArray();

        $this->purchaseService->bulkDeletePurchases($ids);

        foreach ($purchases as $purchase) {
            $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
        }
    }

    public function test_bulk_mark_paid()
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $purchases = collect();
        for ($i = 0; $i < 3; $i++) {
            $purchaseData = [
                'invoice' => 'INV-00' . ($i + 1),
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'discount_total' => 0,
                'discount_total_type' => 'fixed',
                'products' => json_encode([
                    ['product_id' => $product->id, 'quantity' => 1, 'price' => 100],
                ]),
            ];
            $purchases->push($this->purchaseService->createPurchase($purchaseData));
        }

        $ids = $purchases->pluck('id')->toArray();

        $count = $this->purchaseService->bulkMarkPaid($ids);

        $this->assertEquals(3, $count);
        foreach ($purchases as $purchase) {
            $purchase->refresh(); // Refresh the purchase model to get the latest status
            $this->assertEquals('Paid', $purchase->status);
        }
    }

    public function test_get_purchase_metrics()
    {
        Supplier::factory()->create(['location' => 'IN']);
        Supplier::factory()->create(['location' => 'OUT']);
        Purchase::factory()->count(5)->create();

        $metrics = $this->purchaseService->getPurchaseMetrics();

        $this->assertArrayHasKey('totalinvoice', $metrics);
        $this->assertArrayHasKey('inCount', $metrics);
        $this->assertArrayHasKey('inCountamount', $metrics);
        $this->assertArrayHasKey('outCount', $metrics);
        $this->assertArrayHasKey('outCountamount', $metrics);
        $this->assertArrayHasKey('totalMonthly', $metrics);
        $this->assertArrayHasKey('paymentMonthly', $metrics);
    }

    public function test_get_expiring_purchase_count()
    {
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(100), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Paid']);

        $count = $this->purchaseService->getExpiringPurchaseCount();

        $this->assertEquals(1, $count);
    }

    public function test_get_expiring_purchases()
    {
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(100), 'status' => 'Unpaid']);

        $expiringPurchases = $this->purchaseService->getExpiringPurchases();

        $this->assertCount(1, $expiringPurchases);
    }
}
