<!DOCTYPE html>
<html>
<head>
    <title>Aged Receivables Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2, h3, h4 {
            text-align: center;
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
        .fw-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Aged Receivables Report</h1>
    <h2>As of {{ \Carbon\Carbon::now()->format('M d, Y') }}</h2>

    @php
        $buckets = [
            'current' => 'Current',
            '1-30' => '1-30 Days Overdue',
            '31-60' => '31-60 Days Overdue',
            '61-90' => '61-90 Days Overdue',
            '90+' => 'Over 90 Days Overdue',
        ];
    @endphp

    @foreach ($aging as $bucketKey => $invoices)
        @if($invoices->count() > 0)
            <div class="report-section mb-4">
                <h4>{{ $buckets[$bucketKey] }}</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Invoice No</th>
                            <th>Due Date</th>
                            <th class="text-end">Days Overdue</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->customer->name ?? 'Walk-in Customer' }}</td>
                                <td>{{ $invoice->invoice }}</td>
                                <td>{{ \App\Helpers\CurrencyHelper::formatDate($invoice->due_date) }}</td>
                                <td class="text-end">{{ $invoice->days_overdue }}</td>
                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">Total for {{ $buckets[$bucketKey] }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($invoices->sum('total')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endforeach
</body>
</html>
