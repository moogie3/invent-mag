<!DOCTYPE html>
<html>
<head>
    <title>Purchase Order List</title>
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
    <h1>Purchase Order List</h1>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Supplier</th>
                <th>Order Date</th>
                <th>Due Date</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->invoice }}</td>
                    <td>{{ $purchase->supplier->name }}</td>
                    <td>{{ $purchase->order_date->format('Y-m-d') }}</td>
                    <td>{{ $purchase->due_date->format('Y-m-d') }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($purchase->total) }}</td>
                    <td>{{ $purchase->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
