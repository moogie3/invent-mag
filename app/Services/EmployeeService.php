<?php

namespace App\Services;

use App\Models\Employee;
use Dompdf\Dompdf;

class EmployeeService
{
    public function getIndexData()
    {
        $employees = Employee::paginate(20);

        return compact('employees');
    }

    public function getCreateData()
    {
        return [];
    }

    public function getEditData(Employee $employee)
    {
        return compact('employee');
    }

    public function create(array $data): Employee
    {
        $validated = $this->validate($data);

        return Employee::create($validated);
    }

    public function update(Employee $employee, array $data): Employee
    {
        $validated = $this->validateUpdate($data);
        $employee->update($validated);

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    public function exportEmployees(?array $ids, string $exportOption)
    {
        $query = Employee::query();
        if ($ids) {
            $query->whereIn('id', $ids);
        }
        $employees = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.hr.employee.export-pdf', compact('employees'))->render();
            $dompdf = new Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->stream('employees.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=employees.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            $callback = function () use ($employees) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Code', 'Name', 'Department', 'Position', 'Type', 'Status', 'Base Salary', 'Join Date']);
                foreach ($employees as $emp) {
                    fputcsv($file, [
                        $emp->employee_code,
                        $emp->name,
                        $emp->department,
                        $emp->position,
                        $emp->employment_type,
                        $emp->status,
                        number_format($emp->base_salary, 2),
                        $emp->join_date ? $emp->join_date->format('Y-m-d') : '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    protected function validate(array $data, ?int $id = null): array
    {
        return validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['required', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['required', 'string', 'in:permanent,contract,part_time'],
            'join_date' => ['required', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,resigned,terminated'],
            'notes' => ['nullable', 'string'],
            'default_allowances' => ['nullable', 'array'],
            'default_allowances.*.label' => ['required', 'string'],
            'default_allowances.*.amount' => ['required', 'numeric'],
            'default_deductions' => ['nullable', 'array'],
            'default_deductions.*.label' => ['required', 'string'],
            'default_deductions.*.amount' => ['required', 'numeric'],
        ])->validate();
    }

    protected function validateUpdate(array $data): array
    {
        return validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['required', 'string', 'in:permanent,contract,part_time'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,resigned,terminated'],
            'notes' => ['nullable', 'string'],
            'default_allowances' => ['nullable', 'array'],
            'default_allowances.*.label' => ['required', 'string'],
            'default_allowances.*.amount' => ['required', 'numeric'],
            'default_deductions' => ['nullable', 'array'],
            'default_deductions.*.label' => ['required', 'string'],
            'default_deductions.*.amount' => ['required', 'numeric'],
        ])->validate();
    }
}
