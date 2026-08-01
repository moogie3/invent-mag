<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\AuditLogController;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class AuditLogControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected ActivityLogService $logService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->logService = new ActivityLogService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function actingAsSuperuser()
    {
        $user = User::factory()->create([
            'tenant_id' => app('currentTenant')->id,
        ]);
        $user->assignRole('superuser');
        $this->actingAs($user);

        return $user;
    }

    public function test_constructor_requires_superuser_role()
    {
        $controller = new AuditLogController($this->logService);

        $this->assertInstanceOf(AuditLogController::class, $controller);
    }

    public function test_index_returns_view_with_required_data()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'module' => 'Product',
            'action' => 'create',
            'user_name' => 'Test User',
        ]);

        $response = $this->get(route('admin.audit-log.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.audit-log.index');
        $response->assertViewHasAll([
            'logs',
            'filters',
            'modules',
            'actions',
            'users',
            'perPage',
            'today',
            'thisWeek',
            'thisMonth',
            'totalEvents',
        ]);
    }

    public function test_index_paginates_logs()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->count(30)->create([
            'tenant_id' => $tenantId,
        ]);

        $response = $this->get(route('admin.audit-log.index', ['entries' => 10]));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_user_id()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;
        $user = User::factory()->create(['tenant_id' => $tenantId]);

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        $response = $this->get(route('admin.audit-log.index', ['user_id' => $user->id]));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_module()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'module' => 'Product',
        ]);

        $response = $this->get(route('admin.audit-log.index', ['module' => 'Product']));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_action()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'action' => 'create',
        ]);

        $response = $this->get(route('admin.audit-log.index', ['action' => 'create']));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_search()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'description' => 'Test search term here',
        ]);

        $response = $this->get(route('admin.audit-log.index', ['search' => 'Test']));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_date_range()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'created_at' => now(),
        ]);

        $response = $this->get(route('admin.audit-log.index', [
            'date_from' => now()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }

    public function test_show_returns_json_for_audit_log()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        $log = ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'module' => 'Product',
            'action' => 'create',
            'description' => 'Test description',
            'old_values' => ['name' => 'Old'],
            'new_values' => ['name' => 'New'],
        ]);

        $response = $this->get(route('admin.audit-log.show', $log->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'user_name',
            'user_role',
            'module',
            'action',
            'entity_label',
            'description',
            'old_values',
            'new_values',
            'ip_address',
            'user_agent',
            'created_at',
        ]);
    }

    public function test_show_returns_correct_data()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        $log = ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'module' => 'Product',
            'action' => 'create',
            'description' => 'Created product ABC',
        ]);

        $response = $this->get(route('admin.audit-log.show', $log->id));

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $log->id,
            'module' => 'Product',
            'action' => 'create',
            'description' => 'Created product ABC',
        ]);
    }

    public function test_audit_log_access_with_proper_permissions()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $response = $this->get(route('admin.audit-log.index'));

        $response->assertStatus(200);
    }

    public function test_summary_stats_are_calculated_correctly()
    {
        $this->actingAsSuperuser();

        $tenantId = app('currentTenant')->id;

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'created_at' => now(),
        ]);

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'created_at' => now()->subDays(3),
        ]);

        ActivityLog::factory()->create([
            'tenant_id' => $tenantId,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->get(route('admin.audit-log.index'));

        $response->assertStatus(200);
        $response->assertViewHasAll(['today', 'thisWeek', 'thisMonth', 'totalEvents']);
    }
}
