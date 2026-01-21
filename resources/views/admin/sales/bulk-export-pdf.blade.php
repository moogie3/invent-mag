<!DOCTYPE html>
<html>
<head>
    <title>Sales Order List</title>
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
    <h1>Sales Order List</h1>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Order Date</th>
                <th>Due Date</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale->invoice }}</td>
                    <td>{{ $sale->customer->name }}</td>
                    <td>{{ $sale->order_date->format('Y-m-d') }}</td>
                    <td>{{ $sale->due_date->format('Y-m-d') }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($sale->total_amount) }}</td>
                    <td>{{ $sale->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
