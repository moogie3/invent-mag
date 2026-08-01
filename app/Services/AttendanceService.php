<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Dompdf\Dompdf;
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

    public function openPeriod(int $year, int $month, int $workingDays = 21): AttendancePeriod
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
            'working_days' => $workingDays,
            'status' => 'open',
        ]);
    }

    public function closePeriod(AttendancePeriod $period): AttendancePeriod
    {
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $period;
    }

    public function deletePeriod(AttendancePeriod $period): void
    {
        if ($period->isClosed()) {
            throw new \InvalidArgumentException('Cannot delete a closed attendance period.');
        }

        if ($period->payrollRuns()->exists()) {
            throw new \InvalidArgumentException('Cannot delete an attendance period that has associated payroll runs. Delete the payroll runs first.');
        }

        $period->records()->delete();
        $period->delete();
    }

    public function upsertRecord(array $data): AttendanceRecord
    {
        $validated = $this->validateRecord($data);

        return AttendanceRecord::updateOrCreate(
            [
                'tenant_id' => app('currentTenant')->id,
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

    public function bulkFill(AttendancePeriod $period, array $employeeIds, string $status, ?float $overtimeHours = null, array $skipDays = []): int
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

                if (in_array($weekday, $skipDays)) {
                    continue;
                }

                $records[] = [
                    'tenant_id' => app('currentTenant')->id,
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

    public function exportPeriodRecords(AttendancePeriod $period, string $exportOption)
    {
        $employees = Employee::where('status', 'active')->get();
        $records = AttendanceRecord::where('period_id', $period->id)
            ->with('employee')
            ->get()
            ->keyBy('employee_id');

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year);

        if ($exportOption === 'pdf') {
            $html = view('admin.hr.attendance.export-period-pdf', compact('period', 'employees', 'records', 'daysInMonth'))->render();
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->stream("attendance-{$period->year}-{$period->month}.pdf");
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=attendance-{$period->year}-{$period->month}.csv",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            $callback = function () use ($period, $employees, $records, $daysInMonth) {
                $file = fopen('php://output', 'w');
                $headerRow = ['Employee'];
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $headerRow[] = $day;
                }
                fputcsv($file, $headerRow);
                foreach ($employees as $emp) {
                    $row = [$emp->name];
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $date = sprintf('%04d-%02d-%02d', $period->year, $period->month, $day);
                        $record = $records->get($emp->id)?->firstWhere('date', $date);
                        $row[] = $record ? $record->status : '';
                    }
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function exportPeriods(?array $ids, string $exportOption)
    {
        $query = AttendancePeriod::orderBy('year', 'desc')->orderBy('month', 'desc');
        if ($ids) {
            $query->whereIn('id', $ids);
        }
        $periods = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.hr.attendance.export-pdf', compact('periods'))->render();
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->stream('attendance-periods.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=attendance-periods.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            $callback = function () use ($periods) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Period', 'Year', 'Month', 'Working Days', 'Status', 'Closed At']);
                foreach ($periods as $p) {
                    fputcsv($file, [
                        $p->year.'-'.str_pad($p->month, 2, '0', STR_PAD_LEFT),
                        $p->year,
                        $p->month,
                        $p->working_days,
                        $p->status,
                        $p->closed_at ? $p->closed_at->format('Y-m-d') : '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
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
