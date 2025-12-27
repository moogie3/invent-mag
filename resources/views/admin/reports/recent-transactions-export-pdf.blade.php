<!DOCTYPE html>
<html>
<head>
    <title>Recent Transactions</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Recent Transactions</h1>
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
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction['type'] }}</td>
                    <td>{{ $transaction['invoice'] }}</td>
                    <td>{{ $transaction['customer_supplier'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('Y-m-d H:i') }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($transaction['amount']) }}</td>
                    <td>{{ $transaction['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No recent transactions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
