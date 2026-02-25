<!DOCTYPE html>
<html>
<head>
    <title>Income Statement</title>
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
        .net-income {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <h1>Income Statement</h1>
    <h2>For the Period {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</h2>

    <div class="report-section">
        <h4>Revenue</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($revenueAccounts as $account)
                    <tr>
                        <td>{{ __($account->name) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($account->transactions->sum(function ($transaction) {
                            return $transaction->type === 'credit' ? $transaction->amount : -$transaction->amount;
                        })) }}</td>
                    </tr>
                @endforeach
                <tr class="fw-bold">
                    <td>Total Revenue</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalRevenue) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section">
        <h4>Expenses</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenseAccounts as $account)
                    <tr>
                        <td>{{ __($account->name) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($account->transactions->sum(function ($transaction) {
                            return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
                        })) }}</td>
                    </tr>
                @endforeach
                <tr class="fw-bold">
                    <td>Total Expenses</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalExpenses) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section net-income">
        <h3 class="d-flex justify-content-between">
            <span>Net Income</span>
            <span class="text-end">{{ \App\Helpers\CurrencyHelper::format($netIncome) }}</span>
        </h3>
    </div>
</body>
</html>
