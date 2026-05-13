<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.hr_payroll') }} - {{ $run->period->year }}/{{ str_pad($run->period->month, 2, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ __('messages.hr_payroll') }} - {{ $run->period->year }}/{{ str_pad($run->period->month, 2, '0', STR_PAD_LEFT) }}</h1>
    <p>{{ __('messages.status') }}: {{ $run->status }} | {{ __('messages.hr_run_date') }}: {{ $run->run_date ? $run->run_date->format('Y-m-d') : '-' }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.hr_employee') }}</th>
                <th class="text-right">{{ __('messages.hr_base_salary') }}</th>
                <th class="text-right">{{ __('messages.hr_allowances') }}</th>
                <th class="text-right">{{ __('messages.hr_deductions') }}</th>
                <th class="text-right">{{ __('messages.overtime') }}</th>
                <th class="text-right">{{ __('messages.hr_gross_pay') }}</th>
                <th class="text-right">{{ __('messages.hr_net_pay') }}</th>
                <th class="text-right">{{ __('messages.days') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->employee->name }} ({{ $item->employee->employee_code }})</td>
                    <td class="text-right">{{ number_format($item->base_salary, 2) }}</td>
                    <td class="text-right">{{ number_format(collect($item->allowances ?? [])->sum('amount'), 2) }}</td>
                    <td class="text-right">{{ number_format(collect($item->deductions ?? [])->sum('amount'), 2) }}</td>
                    <td class="text-right">{{ number_format($item->overtime_pay, 2) }}</td>
                    <td class="text-right">{{ number_format($item->gross_pay, 2) }}</td>
                    <td class="text-right">{{ number_format($item->net_pay, 2) }}</td>
                    <td class="text-right">{{ $item->present_days }}/{{ $item->working_days }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold; background:#f9f9f9;">
                <td>{{ __('messages.total') }}</td>
                <td class="text-right">{{ number_format($items->sum('base_salary'), 2) }}</td>
                <td class="text-right">{{ number_format($items->sum(fn($i) => collect($i->allowances ?? [])->sum('amount')), 2) }}</td>
                <td class="text-right">{{ number_format($items->sum(fn($i) => collect($i->deductions ?? [])->sum('amount')), 2) }}</td>
                <td class="text-right">{{ number_format($items->sum('overtime_pay'), 2) }}</td>
                <td class="text-right">{{ number_format($items->sum('gross_pay'), 2) }}</td>
                <td class="text-right">{{ number_format($items->sum('net_pay'), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
