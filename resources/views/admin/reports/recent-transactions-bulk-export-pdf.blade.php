<!DOCTYPE html>
<html>

<head>
    <title>Transaction List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
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
    <h1>Transaction List</h1>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Invoice</th>
                <th>Customer/Supplier</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction['type'] }}</td>
                    <td>{{ $transaction['invoice'] }}</td>
                    <td>{{ $transaction['customer_supplier'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('Y-m-d H:i') }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($transaction['amount']) }}</td>
                    <td>{{ $transaction['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
