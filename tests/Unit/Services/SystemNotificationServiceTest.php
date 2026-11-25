<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SystemNotificationService;
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
    public function trial_and_plan_notifications_are_removed()
    {
        $notifications = $this->service->getSystemNotifications();

        $this->assertNull($notifications->firstWhere('id', 'system::trial_status'));
        $this->assertNull($notifications->firstWhere('id', 'system::plan_expiring'));
        $this->assertNull($notifications->firstWhere('id', 'system::plan_expired'));
        $this->assertNull($notifications->firstWhere('id', 'system::user_limit_reached'));
        $this->assertNull($notifications->firstWhere('id', 'system::scheduled_downgrade'));
        $this->assertNull($notifications->firstWhere('id', 'system::recent_upgrade'));
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
    public function dismissed_notifications_are_filtered_out()
    {
        $this->user->update([
            'system_settings' => [
                'dismissed_notifications' => ['system::profile_incomplete'],
            ],
            'shopname' => null,
            'address' => null,
            'avatar' => null,
        ]);

        $notifications = $this->service->getSystemNotifications();

        $this->assertNull($notifications->firstWhere('id', 'system::profile_incomplete'));
    }

    #[Test]
    public function get_system_notification_count_returns_correct_count()
    {
        $count = $this->service->getSystemNotificationCount();
        $notifications = $this->service->getSystemNotifications();

        $this->assertEquals($notifications->count(), $count);
    }
}
