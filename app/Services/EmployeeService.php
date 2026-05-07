<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Validation\Rule;

class EmployeeService
{
    public function getIndexData()
    {
        $employees = Employee::all();
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
        $validated = $this->validate($data, $employee->id);
        $employee->update($validated);
        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    protected function validate(array $data, ?int $id = null): array
    {
        return validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees')->where(fn ($query) => 
                $query->where('tenant_id', tenant('id'))
            )->ignore($id)],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['required', 'string', 'in:permanent,contract,part_time'],
            'join_date' => ['required', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,resigned,terminated'],
            'notes' => ['nullable', 'string'],
        ])->validate();
    }
}