<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        $employees = [
            [
                'name' => 'Aya Neo',
                'employee_code' => 'AYN',
                'department' => 'Marketing',
                'position' => 'Lead Marketing',
                'employment_type' => 'permanent',
                'join_date' => '2019-01-09',
                'base_salary' => 20000000,
                'bank_name' => 'BCA',
                'bank_account' => '978789',
                'status' => 'active',
            ],
            [
                'name' => 'Budi Santoso',
                'employee_code' => 'BS01',
                'department' => 'Sales',
                'position' => 'Sales Executive',
                'employment_type' => 'permanent',
                'join_date' => '2020-03-15',
                'base_salary' => 8500000,
                'bank_name' => 'Mandiri',
                'bank_account' => '1234567890',
                'status' => 'active',
            ],
            [
                'name' => 'Siti Aminah',
                'employee_code' => 'SA02',
                'department' => 'Finance',
                'position' => 'Accountant',
                'employment_type' => 'permanent',
                'join_date' => '2021-06-01',
                'base_salary' => 12000000,
                'bank_name' => 'BNI',
                'bank_account' => '2345678901',
                'status' => 'active',
            ],
            [
                'name' => 'Ahmad Fauzi',
                'employee_code' => 'AF03',
                'department' => 'Warehouse',
                'position' => 'Inventory Staff',
                'employment_type' => 'contract',
                'join_date' => '2022-01-10',
                'base_salary' => 6500000,
                'bank_name' => 'BTN',
                'bank_account' => '3456789012',
                'status' => 'active',
            ],
            [
                'name' => 'Lilis Suryani',
                'employee_code' => 'LS04',
                'department' => 'HRD',
                'position' => 'HR Manager',
                'employment_type' => 'permanent',
                'join_date' => '2018-11-05',
                'base_salary' => 18000000,
                'bank_name' => 'BCA',
                'bank_account' => '4567890123',
                'status' => 'active',
            ],
            [
                'name' => 'Agus Wijaya',
                'employee_code' => 'AW05',
                'department' => 'IT',
                'position' => 'System Admin',
                'employment_type' => 'permanent',
                'join_date' => '2021-08-20',
                'base_salary' => 15000000,
                'bank_name' => 'Mandiri',
                'bank_account' => '5678901234',
                'status' => 'active',
            ],
            [
                'name' => 'Rina Susilowati',
                'employee_code' => 'RS06',
                'department' => 'Sales',
                'position' => 'Sales Supervisor',
                'employment_type' => 'permanent',
                'join_date' => '2019-07-12',
                'base_salary' => 11000000,
                'bank_name' => 'BRI',
                'bank_account' => '6789012345',
                'status' => 'active',
            ],
            [
                'name' => 'Dedi Hermawan',
                'employee_code' => 'DH07',
                'department' => 'Logistics',
                'position' => 'Delivery Driver',
                'employment_type' => 'part_time',
                'join_date' => '2023-02-01',
                'base_salary' => 4500000,
                'bank_name' => 'BCA',
                'bank_account' => '7890123456',
                'status' => 'active',
            ],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                ['tenant_id' => $tenantId, 'employee_code' => $employee['employee_code']],
                $employee
            );
        }
    }
}