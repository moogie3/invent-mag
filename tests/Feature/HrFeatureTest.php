<?php

namespace Tests\Feature;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class HrFeatureTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTenant();

        // Assign enterprise plan to access HR features
        $plan = Plan::create([
            'slug' => 'enterprise',
            'name' => 'Enterprise',
            'price' => 0,
            'features' => ['hr'],
            'stripe_price_id' => '123',
        ]);

        $this->tenant->update([
            'plan_id' => $plan->id,
            'plan_status' => 'active',
        ]);

        $this->tenant->refresh();
        $this->tenant->makeCurrent();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        // Give user superuser role to bypass permissions
        $this->user->assignRole('superuser');
    }

    public function test_can_view_employee_index(): void
    {
        $response = $this->actingAs($this->user)->get('http://'.$this->tenant->domain.'/admin/hr/employee');
        $response->assertStatus(200);
    }

    public function test_can_create_employee(): void
    {
        $response = $this->actingAs($this->user)->post('http://'.$this->tenant->domain.'/admin/hr/employee', [
            'name' => 'John Doe',
            'employee_code' => 'EMP001',
            'employment_type' => 'permanent',
            'join_date' => '2023-01-01',
            'base_salary' => 10000000,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'employee_code' => 'EMP001',
        ]);
    }

    public function test_can_open_attendance_period(): void
    {
        $response = $this->actingAs($this->user)->post('http://'.$this->tenant->domain.'/admin/hr/attendance/period', [
            'year' => 2026,
            'month' => 5,
            'working_days' => 21,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_periods', [
            'year' => 2026,
            'month' => 5,
            'working_days' => 21,
            'status' => 'open',
        ]);
    }

    public function test_can_run_payroll(): void
    {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'base_salary' => 5000000,
            'status' => 'active',
        ]);

        $period = AttendancePeriod::create([
            'tenant_id' => $this->tenant->id,
            'year' => 2026,
            'month' => 5,
            'working_days' => 21,
            'status' => 'closed',
        ]);

        // Manually record 21 days present
        for ($i = 1; $i <= 21; $i++) {
            AttendanceRecord::create([
                'tenant_id' => $this->tenant->id,
                'period_id' => $period->id,
                'employee_id' => $employee->id,
                'date' => '2026-05-'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'status' => 'present',
            ]);
        }

        $response = $this->actingAs($this->user)->post('http://'.$this->tenant->domain.'/admin/hr/payroll/run/'.$period->id);

        $response->assertRedirect();
        $this->assertDatabaseHas('payroll_runs', [
            'period_id' => $period->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('payroll_items', [
            'employee_id' => $employee->id,
            'base_salary' => 5000000,
            'gross_pay' => 5000000,
            'net_pay' => 5000000,
        ]);
    }
}
