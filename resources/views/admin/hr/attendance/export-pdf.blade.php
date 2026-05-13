<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.hr_attendance') }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>{{ __('messages.hr_attendance') }}</h1>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.period') }}</th>
                <th>{{ __('messages.year') }}</th>
                <th>{{ __('messages.month') }}</th>
                <th>{{ __('messages.hr_working_days') }}</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.hr_close_period') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($periods as $p)
                <tr>
                    <td>{{ $p->year }}-{{ str_pad($p->month, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $p->year }}</td>
                    <td>{{ $p->month }}</td>
                    <td>{{ $p->working_days }}</td>
                    <td>{{ $p->status }}</td>
                    <td>{{ $p->closed_at ? $p->closed_at->format('Y-m-d') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
