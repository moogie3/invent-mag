<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.hr_payroll') }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>{{ __('messages.hr_payroll') }}</h1>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.period') }}</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.hr_run_date') }}</th>
                <th>{{ __('messages.hr_paid_date') }}</th>
                <th>{{ __('messages.hr_total_net_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($runs as $run)
                <tr>
                    <td>{{ $run->period->year }}-{{ str_pad($run->period->month, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $run->status }}</td>
                    <td>{{ $run->run_date ? $run->run_date->format('Y-m-d') : '-' }}</td>
                    <td>{{ $run->paid_date ? $run->paid_date->format('Y-m-d') : '-' }}</td>
                    <td>{{ number_format($run->items->sum('net_pay'), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
