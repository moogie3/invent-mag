<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_notifications_api()
    {
        Auth::guard('web')->logout();
        $this->getJson('/api/v1/notifications')->assertStatus(401);
        $this->getJson('/api/v1/notifications/count')->assertStatus(401);
        $this->postJson('/api/v1/notifications/1/mark-as-read')->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_list_notifications()
    {
        $this->mock(NotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDueNotifications')->once()->andReturn(collect([
                ['id' => 'po::1', 'title' => 'New Purchase Order', 'description' => 'A new purchase order has been created.', 'urgency' => 'high', 'route' => '/purchase-orders/1'],
            ]));
        });

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJsonStructure([
                'notifications' => [
                    '*' => ['id', 'title', 'description', 'urgency', 'route'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_get_notification_count()
    {
        $this->mock(NotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getNotificationCounts')->once()->andReturn(['total' => 1]);
        });

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/count')
            ->assertStatus(200)
            ->assertJson(['count' => 1]);
    }

    #[Test]
    public function authenticated_user_can_mark_notification_as_read()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/notifications/po::1/mark-as-read")
            ->assertStatus(200);
    }
}