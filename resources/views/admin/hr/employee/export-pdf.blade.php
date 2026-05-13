<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.hr_employees') }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>{{ __('messages.hr_employees') }}</h1>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.hr_employee_code') }}</th>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.hr_department') }}</th>
                <th>{{ __('messages.hr_position') }}</th>
                <th>{{ __('messages.hr_employment_type') }}</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.hr_base_salary') }}</th>
                <th>{{ __('messages.hr_join_date') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
                <tr>
                    <td>{{ $emp->employee_code }}</td>
                    <td>{{ $emp->name }}</td>
                    <td>{{ $emp->department }}</td>
                    <td>{{ $emp->position }}</td>
                    <td>{{ $emp->employment_type }}</td>
                    <td>{{ $emp->status }}</td>
                    <td>{{ number_format($emp->base_salary, 2) }}</td>
                    <td>{{ $emp->join_date ? $emp->join_date->format('Y-m-d') : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
