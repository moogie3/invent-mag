<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $employees = Employee::where('tenant_id', $tenantId)->get();

        if ($employees->isEmpty()) {
            return;
        }

        // Create a period for last month
        $lastMonth = now()->subMonth();
        $year = $lastMonth->year;
        $month = $lastMonth->month;

        $period = AttendancePeriod::firstOrCreate([
            'tenant_id' => $tenantId,
            'year' => $year,
            'month' => $month,
        ], [
            'status' => 'closed', // We mark it closed so it's ready for payroll
            'working_days' => 22,
            'closed_at' => now(),
        ]);

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        foreach ($employees as $employee) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $weekday = date('N', strtotime($date));

                // Skip weekends
                if ($weekday >= 6) {
                    continue;
                }

                // Randomly assign some absences, sick leaves
                $rand = rand(1, 100);
                $status = 'present';
                $overtime = 0;

                if ($rand <= 5) {
                    $status = 'absent';
                } elseif ($rand <= 10) {
                    $status = 'sick';
                } elseif ($rand <= 15) {
                    $status = 'leave';
                } elseif ($rand >= 90) {
                    // Overtime on 10% of present days
                    $overtime = rand(1, 3);
                }

                AttendanceRecord::updateOrCreate([
                    'tenant_id' => $tenantId,
                    'employee_id' => $employee->id,
                    'period_id' => $period->id,
                    'date' => $date,
                ], [
                    'status' => $status,
                    'overtime_hours' => $overtime,
                ]);
            }
        }
    }
}
