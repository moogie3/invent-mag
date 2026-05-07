<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;

class PayrollService
{
    public function getIndexData()
    {
        $runs = PayrollRun::with('period')->orderBy('run_date', 'desc')->get();
        return compact('runs');
    }

    public function getRunShowData(PayrollRun $run)
    {
        $items = PayrollItem::where('payroll_run_id', $run->id)
            ->with('employee')
            ->get();
        return compact('run', 'items');
    }

    public function runPayroll(AttendancePeriod $period): PayrollRun
    {
        if ($period->isClosed()) {
            throw new \InvalidArgumentException('Cannot run payroll on closed period.');
        }

        $existingRun = PayrollRun::where('period_id', $period->id)->first();
        if ($existingRun) {
            throw new \InvalidArgumentException('Payroll already run for this period.');
        }

        $employees = Employee::where('status', 'active')->get();
        
        $payrollRun = PayrollRun::create([
            'period_id' => $period->id,
            'status' => 'draft',
            'run_date' => now(),
        ]);

        foreach ($employees as $employee) {
            $this->createPayrollItem($payrollRun, $employee, $period);
        }

        return $payrollRun;
    }

    protected function createPayrollItem(PayrollRun $run, Employee $employee, AttendancePeriod $period): PayrollItem
    {
        $records = AttendanceRecord::where('period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->get();

        $presentDays = $records->where('status', 'present')->count();
        $absentDays = $records->where('status', 'absent')->count();
        $overtimeHours = $records->sum('overtime_hours');
        $workingDays = $records->count();
        
        $overtimePay = $overtimeHours * ($employee->base_salary / 176);

        return PayrollItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'base_salary' => $employee->base_salary,
            'allowances' => [],
            'deductions' => [],
            'overtime_pay' => $overtimePay,
            'gross_pay' => 0,
            'net_pay' => 0,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'overtime_hours' => $overtimeHours,
        ]);
    }

    public function updateItem(PayrollItem $item, array $data): PayrollItem
    {
        $item->update([
            'allowances' => $data['allowances'] ?? [],
            'deductions' => $data['deductions'] ?? [],
        ]);

        $item->gross_pay = $item->calculateGross();
        $item->net_pay = $item->calculateNet();
        $item->save();

        return $item;
    }

    public function markPaid(PayrollRun $run): PayrollRun
    {
        if ($run->isPaid()) {
            throw new \InvalidArgumentException('Already marked as paid.');
        }

        $run->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);

        $this->postJournalEntry($run);

        return $run;
    }

    protected function postJournalEntry(PayrollRun $run): void
    {
        $totalNet = $run->items->sum('net_pay');
        $totalOvertime = $run->items->sum('overtime_pay');

        $journalService = app(GeneralLedgerService::class);
        $journalService->createEntry([
            'date' => $run->paid_date,
            'description' => "Payroll {$run->period->year}/{$run->period->month}",
            'debits' => [
                ['amount' => $totalNet, 'description' => 'Salary Expense'],
            ],
            'credits' => [
                ['amount' => $totalNet, 'description' => 'Salary Payable'],
            ],
        ]);
    }

    public function generatePayslip(PayrollItem $item): string
    {
        $pdfPath = "payslips/{$item->payrollRun->period->year}-{$item->payrollRun->period->month}/{$item->employee->employee_code}.pdf";
        
        $item->update([
            'pdf_path' => $pdfPath,
            'pdf_generated_at' => now(),
        ]);

        return $pdfPath;
    }
}