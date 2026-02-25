<!DOCTYPE html>
<html>
<head>
    <title>Aged Payables Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 1.2em;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-end {
            text-align: right;
        }
        .report-date {
            text-align: right;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Aged Payables Report</h1>
    <p class="report-date">Report Date: {{ \Carbon\Carbon::now()->format('Y-m-d') }}</p>

    @foreach ($aging as $bucket => $bills)
        @if($bills->count() > 0)
            <h2>{{ __('messages.bucket_' . $bucket) }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('messages.supplier') }}</th>
                        <th>{{ __('messages.invoice_no') }}</th>
                        <th>{{ __('messages.due_date') }}</th>
                        <th class="text-end">{{ __('messages.days_overdue') }}</th>
                        <th class="text-end">{{ __('messages.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bills as $bill)
                        <tr>
                            <td>{{ $bill->supplier->name ?? __('messages.unknown_supplier') }}</td>
                            <td>{{ $bill->invoice }}</td>
                            <td>{{ \App\Helpers\CurrencyHelper::formatDate($bill->due_date) }}</td>
                            <td class="text-end">{{ $bill->days_overdue }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($bill->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">{{ __('messages.total_for_bucket', ['bucket' => $bucket]) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($bills->sum('total')) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endforeach
</body>
</html>
