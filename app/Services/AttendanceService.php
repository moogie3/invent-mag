<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Validation\Rule;

class AttendanceService
{
    public function getIndexData()
    {
        $periods = AttendancePeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        return compact('periods');
    }

    public function getPeriodShowData(AttendancePeriod $period)
    {
        $employees = Employee::where('status', 'active')->get();
        $records = AttendanceRecord::where('period_id', $period->id)
            ->with('employee')
            ->get()
            ->keyBy('employee_id');
        return compact('period', 'employees', 'records');
    }

    public function openPeriod(int $year, int $month): AttendancePeriod
    {
        $existing = AttendancePeriod::where('year', $year)
            ->where('month', $month)
            ->first();
        
        if ($existing) {
            throw new \InvalidArgumentException("Period {$year}-{$month} already exists.");
        }

        return AttendancePeriod::create([
            'year' => $year,
            'month' => $month,
            'status' => 'open',
        ]);
    }

    public function closePeriod(AttendancePeriod $period): AttendancePeriod
    {
        if ($period->isClosed()) {
            throw new \InvalidArgumentException('Period is already closed.');
        }

        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $period;
    }

    public function upsertRecord(array $data): AttendanceRecord
    {
        $validated = $this->validateRecord($data);

        return AttendanceRecord::updateOrCreate(
            [
                'tenant_id' => tenant('id'),
                'employee_id' => $validated['employee_id'],
                'period_id' => $validated['period_id'],
                'date' => $validated['date'],
            ],
            [
                'status' => $validated['status'],
                'overtime_hours' => $validated['overtime_hours'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]
        );
    }

    public function bulkFill(AttendancePeriod $period, array $employeeIds, string $status, ?float $overtimeHours = null): int
    {
        if ($period->isClosed()) {
            throw new \InvalidArgumentException('Cannot modify closed period.');
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year);
        $records = [];

        foreach ($employeeIds as $employeeId) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $period->year, $period->month, $day);
                $weekday = date('N', strtotime($date));
                
                if ($weekday >= 6) {
                    continue;
                }

                $records[] = [
                    'tenant_id' => tenant('id'),
                    'employee_id' => $employeeId,
                    'period_id' => $period->id,
                    'date' => $date,
                    'status' => $status,
                    'overtime_hours' => $overtimeHours ?? 0,
                ];
            }
        }

        foreach ($records as $record) {
            AttendanceRecord::updateOrCreate(
                [
                    'tenant_id' => $record['tenant_id'],
                    'employee_id' => $record['employee_id'],
                    'period_id' => $record['period_id'],
                    'date' => $record['date'],
                ],
                $record
            );
        }

        return count($records);
    }

    protected function validateRecord(array $data): array
    {
        return validator($data, [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'period_id' => ['required', 'integer', Rule::exists('attendance_periods', 'id')],
            'date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:present,absent,sick,leave,holiday,half_day'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ])->validate();
    }
}