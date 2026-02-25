<!DOCTYPE html>
<html>
<head>
    <title>Balance Sheet</title>
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
    <h1>Balance Sheet</h1>
    <h2>As of {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</h2>

    <div class="report-section mb-4">
        <h4>Assets</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assets as $account)
                    <tr>
                        <td>{{ __($account->name) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($account->calculated_balance) }}</td>
                    </tr>
                @endforeach
                <tr class="fw-bold">
                    <td>Total Assets</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalAssets) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section mb-4">
        <h4>Liabilities</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($liabilities as $account)
                    <tr>
                        <td>{{ __($account->name) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($account->calculated_balance) }}</td>
                    </tr>
                @endforeach
                <tr class="fw-bold">
                    <td>Total Liabilities</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalLiabilities) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section mb-4">
        <h4>Equity</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($equity as $account)
                    <tr>
                        <td>{{ __($account->name) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($account->calculated_balance) }}</td>
                    </tr>
                @endforeach
                <tr class="fw-bold">
                    <td>Total Equity</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalEquity) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section accounting-equation mt-5 border-top pt-3">
        <h3 class="d-flex justify-content-between">
            <span>Total Liabilities & Equity</span>
            <span class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalLiabilities + $totalEquity) }}</span>
        </h3>
    </div>
</body>
</html>
