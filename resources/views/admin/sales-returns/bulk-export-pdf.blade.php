<!DOCTYPE html>
<html>
<head>
    <title>Sales Return List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Sales Return List</h1>
    <table>
        <thead>
            <tr>
                <th>Sales Invoice</th>
                <th>Return Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Returned By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesReturns as $sr)
                <tr>
                    <td>{{ $sr->sale->invoice }}</td>
                    <td>{{ $sr->return_date->format('Y-m-d') }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($sr->total_amount) }}</td>
                    <td>{{ $sr->status }}</td>
                    <td>{{ $sr->user->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
