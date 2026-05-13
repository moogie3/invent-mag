<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Dompdf\Dompdf;

class PayrollService
{
    public function getIndexData()
    {
        $runs = PayrollRun::with('period')->orderBy('run_date', 'desc')->get();

        $runPeriodIds = $runs->pluck('period_id')->toArray();
        $availablePeriods = AttendancePeriod::whereNotIn('id', $runPeriodIds)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return compact('runs', 'availablePeriods');
    }

    public function getRunShowData(PayrollRun $run)
    {
        $items = PayrollItem::where('payroll_run_id', $run->id)
            ->with('employee')
            ->get();

        $accounts = Account::where('type', 'asset')->where('is_active', true)->get();

        return compact('run', 'items', 'accounts');
    }

    public function runPayroll(AttendancePeriod $period): PayrollRun
    {
        // Can run payroll on any period, but usually you want to run it on closed periods to be safe.
        // Let's remove the restriction that it must be open, or change it so you can only run if it IS closed.
        // For maximum flexibility, let's just check if it's already run.
        $existingRun = PayrollRun::where('period_id', $period->id)->where('tenant_id', app('currentTenant')->id)->first();
        if ($existingRun) {
            throw new \InvalidArgumentException('Payroll already run for this period.');
        }

        $employees = Employee::where('status', 'active')->where('tenant_id', app('currentTenant')->id)->get();

        $payrollRun = PayrollRun::create([
            'tenant_id' => app('currentTenant')->id,
            'period_id' => $period->id,
            'status' => 'draft',
            'run_date' => now(),
        ]);

        foreach ($employees as $employee) {
            $this->createPayrollItem($payrollRun, $employee, $period);
        }

        return $payrollRun;
    }

    public function deletePayrollRun(PayrollRun $run): void
    {
        if ($run->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft payroll runs can be deleted.');
        }

        $run->items()->delete();
        $run->delete();
    }

    protected function createPayrollItem(PayrollRun $run, Employee $employee, AttendancePeriod $period): PayrollItem
    {
        $records = AttendanceRecord::where('period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->get();

        $presentDays = $records->whereIn('status', ['present', 'sick', 'leave', 'holiday'])->count(); // Treated as paid days
        $halfDays = $records->where('status', 'half_day')->count();
        $explicitAbsentDays = $records->where('status', 'absent')->count();
        $overtimeHours = $records->sum('overtime_hours');

        // Use the exact custom working days defined in the Attendance Period configuration.
        // If the company works Mon-Sat, they can set this to 26 when opening the period.
        $workingDays = $period->working_days ?? 21;

        $overtimePay = $overtimeHours * ($employee->base_salary / 176);

        // Calculate Absence Deduction
        // We consider any day that is NOT explicitly a "Paid Day" as an unpaid day.
        // This means unrecorded days are treated as absences for payroll purposes.
        $unpaidDays = $workingDays - ($presentDays + ($halfDays * 0.5));

        $absenceDeductionAmount = 0;
        if ($workingDays > 0 && $unpaidDays > 0) {
            $absenceDeductionAmount = ($employee->base_salary / $workingDays) * $unpaidDays;
        }

        $defaultAllowances = $employee->default_allowances ?? [];
        $defaultDeductions = $employee->default_deductions ?? [];

        if ($absenceDeductionAmount > 0) {
            $defaultDeductions[] = [
                'label' => __('messages.hr_absence_deduction'),
                'amount' => round($absenceDeductionAmount, 2)
            ];
        }

        $item = new PayrollItem([
            'tenant_id' => app('currentTenant')->id,
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'base_salary' => $employee->base_salary,
            'allowances' => $defaultAllowances,
            'deductions' => $defaultDeductions,
            'overtime_pay' => $overtimePay,
            'working_days' => $workingDays,
            'present_days' => $presentDays + ($halfDays * 0.5),
            'absent_days' => $unpaidDays,
            'overtime_hours' => $overtimeHours,
        ]);

        $item->gross_pay = $item->calculateGross();
        $item->net_pay = $item->calculateNet();
        $item->save();

        return $item;
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

    public function approve(PayrollRun $run): PayrollRun
    {
        if ($run->status !== 'draft') {
            throw new \InvalidArgumentException('Can only approve draft payrolls.');
        }

        $run->update(['status' => 'approved']);
        $this->postApprovalJournalEntry($run);

        return $run;
    }

    public function markPaid(PayrollRun $run, int $accountId): PayrollRun
    {
        if ($run->status !== 'approved') {
            throw new \InvalidArgumentException('Payroll must be approved before recording payment.');
        }

        $run->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);

        $this->postPaymentJournalEntry($run, $accountId);

        return $run;
    }

    protected function postApprovalJournalEntry(PayrollRun $run): void
    {
        $totalNet = $run->items->sum('net_pay');
        if ($totalNet <= 0) return;

        $accountingService = app(AccountingService::class);

        $transactions = [
            [
                'account_name' => 'Salary Expense', // You may replace this with mapped settings
                'type' => 'debit',
                'amount' => $totalNet
            ],
            [
                'account_name' => 'Salary Payable',
                'type' => 'credit',
                'amount' => $totalNet
            ]
        ];

        $accountingService->createJournalEntry(
            "Payroll Accrual {$run->period->year}/{$run->period->month}",
            $run->run_date,
            $transactions,
            $run
        );
    }

    protected function postPaymentJournalEntry(PayrollRun $run, int $accountId): void
    {
        $totalNet = $run->items->sum('net_pay');
        if ($totalNet <= 0) return;

        $paymentAccount = Account::find($accountId);
        if (!$paymentAccount) {
            throw new \InvalidArgumentException("Payment account not found.");
        }

        $accountingService = app(AccountingService::class);

        $transactions = [
            [
                'account_name' => 'Salary Payable',
                'type' => 'debit',
                'amount' => $totalNet
            ],
            [
                'account_id' => $accountId,
                'account_name' => $paymentAccount->name,
                'type' => 'credit',
                'amount' => $totalNet
            ]
        ];

        $accountingService->createJournalEntry(
            "Payroll Payment {$run->period->year}/{$run->period->month}",
            $run->paid_date,
            $transactions,
            $run
        );
    }

    public function exportRunItems(PayrollRun $run, string $exportOption)
    {
        $items = PayrollItem::where('payroll_run_id', $run->id)
            ->with('employee')
            ->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.hr.payroll.export-run-pdf', compact('run', 'items'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream("payroll-{$run->period->year}-{$run->period->month}.pdf");
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=payroll-{$run->period->year}-{$run->period->month}.csv",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            $callback = function () use ($items) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Employee', 'Code', 'Base Salary', 'Allowances', 'Deductions', 'Overtime', 'Gross Pay', 'Net Pay', 'Present/Working']);
                foreach ($items as $item) {
                    fputcsv($file, [
                        $item->employee->name,
                        $item->employee->employee_code,
                        number_format($item->base_salary, 2),
                        number_format(collect($item->allowances ?? [])->sum('amount'), 2),
                        number_format(collect($item->deductions ?? [])->sum('amount'), 2),
                        number_format($item->overtime_pay, 2),
                        number_format($item->gross_pay, 2),
                        number_format($item->net_pay, 2),
                        "{$item->present_days}/{$item->working_days}",
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function exportRuns(?array $ids, string $exportOption)
    {
        $query = PayrollRun::with('period')->orderBy('run_date', 'desc');
        if ($ids) {
            $query->whereIn('id', $ids);
        }
        $runs = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.hr.payroll.export-pdf', compact('runs'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('payroll-runs.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=payroll-runs.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            $callback = function () use ($runs) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Period', 'Status', 'Run Date', 'Paid Date', 'Total Net Pay']);
                foreach ($runs as $run) {
                    fputcsv($file, [
                        $run->period->year.'-'.str_pad($run->period->month, 2, '0', STR_PAD_LEFT),
                        $run->status,
                        $run->run_date ? $run->run_date->format('Y-m-d') : '',
                        $run->paid_date ? $run->paid_date->format('Y-m-d') : '',
                        number_format($run->items->sum('net_pay'), 2),
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        return null;
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
