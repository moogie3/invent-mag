<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\Feature\BaseFeatureTestCase;

class NotificationControllerTest extends BaseFeatureTestCase
{
    use WithFaker;

    protected $adminUser;
    protected $notificationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create the superuser role if it doesn't exist
        $superUserRole = Role::firstOrCreate(['name' => 'superuser']);
        // Assign the superuser role to the admin user
        $this->adminUser->assignRole($superUserRole);

        // Mock the NotificationService
        $this->notificationServiceMock = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $this->notificationServiceMock);
    }

    public function test_index_displays_notifications_page()
    {
        $this->notificationServiceMock->shouldReceive('getDueNotifications')->andReturn(collect([]));

        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.notifications');
        $response->assertViewHas('notifications');
        $response->assertViewHas('hasNotifications');
        $response->assertViewHas('financialNotifications');
        $response->assertViewHas('lowStockNotifications');
        $response->assertViewHas('expiringNotifications');
    }

    public function test_count_returns_notification_count()
    {
        $this->notificationServiceMock->shouldReceive('getNotificationCounts')->andReturn(['total' => 5]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.count'));

        $response->assertStatus(200);
        $response->assertJson(['count' => 5]);
    }

    public function test_get_notifications_returns_simplified_notifications()
    {
        $notifications = collect([
            [
                'id' => 'po::1',
                'title' => 'PO Overdue',
                'description' => 'Purchase Order PO-123 is overdue.',
                'urgency' => 'high',
                'route' => route('admin.po.view', 1),
            ],
        ]);
        $this->notificationServiceMock->shouldReceive('getDueNotifications')->andReturn($notifications);

        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.list'));

        $response->assertStatus(200);
        $response->assertJson([
            'notifications' => [
                [
                    'id' => 'po::1',
                    'title' => 'PO Overdue',
                    'description' => 'Purchase Order PO-123 is overdue.',
                    'urgency' => 'high',
                    'route' => route('admin.po.view', 1),
                ],
            ],
        ]);
    }

    public function test_view_redirects_to_correct_route()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.view', 'po::1'));
        $response->assertRedirect(route('admin.po.view', 1));

        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.view', 'sale::1'));
        $response->assertRedirect(route('admin.sales.view', 1));

        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.view', 'product::1'));
        $response->assertRedirect(route('admin.product.edit', 1));
    }

    public function test_view_handles_invalid_id()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.view', 'invalid-id'));
        $response->assertRedirect(route('admin.notifications'));
        $response->assertSessionHas('error', 'Invalid notification ID');
    }

    public function test_view_handles_unknown_type()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.view', 'unknown::1'));
        $response->assertRedirect(route('admin.notifications'));
        $response->assertSessionHas('error', 'Unknown notification type');
    }

    public function test_mark_as_read_returns_success()
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.notifications.mark-read', 1));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}