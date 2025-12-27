<!DOCTYPE html>
<html>
<head>
    <title>Purchase Return List</title>
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
    <h1>Purchase Return List</h1>
    <table>
        <thead>
            <tr>
                <th>Purchase Invoice</th>
                <th>Return Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Returned By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseReturns as $pr)
                <tr>
                    <td>{{ $pr->purchase->invoice }}</td>
                    <td>{{ $pr->return_date->format('Y-m-d') }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($pr->total_amount) }}</td>
                    <td>{{ $pr->status }}</td>
                    <td>{{ $pr->user->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
