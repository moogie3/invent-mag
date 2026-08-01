<?php

namespace Database\Seeders;

use App\Models\AttendancePeriod;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        $lastMonth = now()->subMonth();
        $period = AttendancePeriod::where('tenant_id', $tenantId)
            ->where('year', $lastMonth->year)
            ->where('month', $lastMonth->month)
            ->first();

        if (! $period) {
            return;
        }

        // Avoid duplicate runs
        if (PayrollRun::where('tenant_id', $tenantId)->where('period_id', $period->id)->exists()) {
            return;
        }

        $service = app(PayrollService::class);

        // 1. Run Payroll
        $run = $service->runPayroll($period);

        // 2. Approve Payroll
        $service->approve($run);

        // We don't mark as paid so the user can test the 'Record Payment' flow themselves.
    }
}
