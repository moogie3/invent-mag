<?php

namespace Tests\Unit\Services;

use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\Payment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class NotificationServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->notificationService = new NotificationService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(NotificationService::class, $this->notificationService);
    }

    #[Test]
    public function it_can_get_due_notifications()
    {
        // 1. Setup data
        // Purchase due in 3 days
        Purchase::factory()->create(['due_date' => now()->addDays(3), 'status' => 'Unpaid']);
        $sale = Sales::factory()->create(['due_date' => now(), 'status' => 'Unpaid']);
        // Low stock product
        Product::factory()->create(['stock_quantity' => 5, 'low_stock_threshold' => 10]);
        // Expiring POItem
        $product = Product::factory()->create();
        $purchase = Purchase::factory()->create(['due_date' => now()->addYear(), 'status' => 'Paid']);
        POItem::factory()->create(['product_id' => $product->id, 'po_id' => $purchase->id, 'expiry_date' => now()->addDays(20)]);
        
        // Paid purchase - should not be in notifications
        $paidPurchase = Purchase::factory()->create(['due_date' => now()->addDays(1), 'status' => 'Paid']);
        Payment::factory()->create([
            'paymentable_id' => $paidPurchase->id,
            'paymentable_type' => Purchase::class,
            'payment_date' => now()->subDay()
        ]);

        // Paid today - should be in notifications
        $paidTodayPurchase = Purchase::factory()->create(['due_date' => now()->addDays(2), 'status' => 'Paid']);
        Payment::factory()->create([
            'paymentable_id' => $paidTodayPurchase->id,
            'paymentable_type' => Purchase::class,
            'payment_date' => now()
        ]);


        // 2. Call the service method
        $notifications = $this->notificationService->getDueNotifications();

        // 3. Assertions
        $this->assertCount(5, $notifications);

        $notificationTitles = $notifications->pluck('title');
        $this->assertContains('Due Purchase: PO #1', $notificationTitles);
        $this->assertContains('Due Invoice: #' . $sale->invoice, $notificationTitles);
        $this->assertTrue($notifications->contains(function ($value, $key) {
            return str_starts_with($value['title'], 'Low Stock Alert');
        }));
        $this->assertTrue($notifications->contains(function ($value, $key) {
            return str_starts_with($value['title'], 'Expiring Product');
        }));
        $this->assertTrue($notifications->contains(function ($value, $key) {
            return $value['status_text'] === 'Paid Today';
        }));
    }

    #[Test]
    public function it_can_get_notification_counts()
    {
        // 1. Setup data
        // Due purchases
        Purchase::factory()->count(2)->create(['due_date' => now()->addDays(3), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Unpaid']); // This should not be counted
        Purchase::factory()->create(['due_date' => now()->addDays(3), 'status' => 'Paid']); // This should not be counted

        // Due sales
        Sales::factory()->count(3)->create(['due_date' => now(), 'status' => 'Unpaid']);
        Sales::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Unpaid']); // This should not be counted
        Sales::factory()->create(['due_date' => now(), 'status' => 'Paid']); // This should not be counted

        // Low stock products
        Product::factory()->count(4)->create(['stock_quantity' => 5, 'low_stock_threshold' => 10]);
        Product::factory()->create(['stock_quantity' => 15, 'low_stock_threshold' => 10]); // This should not be counted

        // 2. Call the service method
        $counts = $this->notificationService->getNotificationCounts();

        // 3. Assertions
        $this->assertEquals(2, $counts['poCount']);
        $this->assertEquals(3, $counts['salesCount']);
        $this->assertEquals(4, $counts['lowStockCount']);
        $this->assertEquals(9, $counts['total']);
    }
}
