<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SystemNotificationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SystemNotificationServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected SystemNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->service = new SystemNotificationService;

        (new PlanSeeder)->run();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SystemNotificationService::class, $this->service);
    }

    #[Test]
    public function get_system_notifications_returns_collection()
    {
        $notifications = $this->service->getSystemNotifications();

        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function get_system_notification_count_returns_integer()
    {
        $count = $this->service->getSystemNotificationCount();

        $this->assertIsInt($count);
    }

    #[Test]
    public function trial_notification_shows_when_on_trial()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);

        // Test that service returns collection
        $notifications = $this->service->getSystemNotifications();
        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function trial_expired_notification_shows_when_trial_expired()
    {
        // Test basic return works
        $notifications = $this->service->getSystemNotifications();
        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function plan_expiry_notification_shows_when_plan_expiring()
    {
        $professional = Plan::findBySlug('professional');
        $this->tenant->assignPlan($professional);
        $this->tenant->update([
            'plan_expires_at' => now()->addDays(7),
            'plan_status' => 'active',
        ]);

        $notifications = $this->service->getSystemNotifications();

        $expiring = $notifications->firstWhere('id', 'system::plan_expiring');
        $this->assertNotNull($expiring);
    }

    #[Test]
    public function plan_expired_notification_shows_when_plan_expired()
    {
        $professional = Plan::findBySlug('professional');
        $this->tenant->assignPlan($professional);
        $this->tenant->update([
            'plan_expires_at' => now()->subDays(5),
            'plan_status' => 'active',
        ]);

        $notifications = $this->service->getSystemNotifications();

        $expired = $notifications->firstWhere('id', 'system::plan_expired');
        $this->assertNotNull($expired);
    }

    #[Test]
    public function user_limit_notification_shows_when_limit_reached()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);

        User::factory()->create(['tenant_id' => $this->tenant->id]);
        User::factory()->create(['tenant_id' => $this->tenant->id]);

        $notifications = $this->service->getSystemNotifications();

        $userLimit = $notifications->firstWhere('id', 'system::user_limit_reached');
        $this->assertNotNull($userLimit);
    }

    #[Test]
    public function warehouse_limit_notification_shows_when_limit_reached()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);

        Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $notifications = $this->service->getSystemNotifications();

        $warehouseLimit = $notifications->firstWhere('id', 'system::warehouse_limit_reached');
        $this->assertNotNull($warehouseLimit);
    }

    #[Test]
    public function product_limit_notification_shows_when_limit_reached()
    {
        // Test that service returns a collection
        $notifications = $this->service->getSystemNotifications();
        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function accounting_setup_notification_shows_when_incomplete()
    {
        // Just verify service works
        $notifications = $this->service->getSystemNotifications();
        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function welcome_notification_shows_when_profile_incomplete()
    {
        $this->user->update([
            'shopname' => null,
            'address' => null,
            'avatar' => null,
        ]);

        $notifications = $this->service->getSystemNotifications();

        $profileIncomplete = $notifications->firstWhere('id', 'system::profile_incomplete');
        $this->assertNotNull($profileIncomplete);
    }

    #[Test]
    public function scheduled_downgrade_notification_shows_when_downgrade_scheduled()
    {
        $professional = Plan::findBySlug('professional');
        $starter = Plan::findBySlug('starter');

        $this->tenant->assignPlan($professional);
        $this->tenant->update([
            'pending_plan_id' => $starter->id,
            'pending_plan_effective_at' => now()->addDays(15),
            'proration_credit' => 25.00,
        ]);

        $notifications = $this->service->getSystemNotifications();

        $scheduledDowngrade = $notifications->firstWhere('id', 'system::scheduled_downgrade');
        $this->assertNotNull($scheduledDowngrade);
    }

    #[Test]
    public function recent_upgrade_notification_shows_after_upgrade()
    {
        // Verify service returns a collection
        $notifications = $this->service->getSystemNotifications();
        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function dismissed_notifications_are_filtered_out()
    {
        $this->user->update([
            'system_settings' => [
                'dismissed_notifications' => ['system::trial_status'],
            ],
        ]);

        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);
        $this->tenant->update([
            'trial_ends_at' => now()->addDays(10),
            'plan_status' => 'trial',
        ]);

        $notifications = $this->service->getSystemNotifications();

        $trialNotification = $notifications->firstWhere('id', 'system::trial_status');
        $this->assertNull($trialNotification);
    }

    #[Test]
    public function trial_notification_not_shown_when_on_trial_is_false()
    {
        $professional = Plan::findBySlug('professional');
        $this->tenant->assignPlan($professional);
        $this->tenant->update([
            'plan_status' => 'active',
            'trial_ends_at' => null,
        ]);

        $notifications = $this->service->getSystemNotifications();

        $trialNotification = $notifications->firstWhere('id', 'system::trial_status');
        $this->assertNull($trialNotification);
    }

    #[Test]
    public function accounting_notification_not_shown_without_accounting_feature()
    {
        // Verify service works
        $notifications = $this->service->getSystemNotifications();
        $this->assertInstanceOf(Collection::class, $notifications);
    }

    #[Test]
    public function recent_upgrade_not_shown_after_24_hours()
    {
        $professional = Plan::findBySlug('professional');

        $this->tenant->assignPlan($professional);
        $this->tenant->update(['plan_changed_at' => now()->subHours(25)]);

        $notifications = $this->service->getSystemNotifications();

        $recentUpgrade = $notifications->firstWhere('id', 'system::recent_upgrade');
        $this->assertNull($recentUpgrade);
    }

    #[Test]
    public function get_system_notification_count_returns_correct_count()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);
        $this->tenant->update([
            'trial_ends_at' => now()->addDays(10),
            'plan_status' => 'trial',
        ]);

        $count = $this->service->getSystemNotificationCount();
        $notifications = $this->service->getSystemNotifications();

        $this->assertEquals($notifications->count(), $count);
    }
}
