<!DOCTYPE html>
<html>
<head>
    <title>General Ledger</title>
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
    <h1>General Ledger</h1>
    <p><strong>Account:</strong> {{ $account->name }}</p>
    <p><strong>Period:</strong> {{ $startDate }} to {{ $endDate }}</p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td><strong>Opening Balance</strong></td>
                <td></td>
                <td></td>
                <td><strong>{{ \App\Helpers\CurrencyHelper::format($openingBalance) }}</strong></td>
            </tr>
            @php
                $balance = $openingBalance;
            @endphp
            @foreach($transactions as $transaction)
                @php
                    $balance += $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
                @endphp
                <tr>
                    <td>{{ $transaction->journalEntry->date->format('Y-m-d') }}</td>
                    <td>{{ $transaction->journalEntry->description }}</td>
                    <td>{{ $transaction->type == 'debit' ? \App\Helpers\CurrencyHelper::format($transaction->amount) : '' }}</td>
                    <td>{{ $transaction->type == 'credit' ? \App\Helpers\CurrencyHelper::format($transaction->amount) : '' }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($balance) }}</td>
                </tr>
            @endforeach
            <tr>
                <td></td>
                <td><strong>Closing Balance</strong></td>
                <td></td>
                <td></td>
                <td><strong>{{ \App\Helpers\CurrencyHelper::format($closingBalance) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
