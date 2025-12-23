<!DOCTYPE html>
<html>
<head>
    <title>Trial Balance</title>
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
    <h1>Trial Balance</h1>
    <p><strong>As of:</strong> {{ $endDate }}</p>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $item)
                <tr>
                    <td class="text-muted">{{ $item['code'] }}</td>
                    <td>{{ __($item['name']) }}</td>
                    <td class="text-end">
                        {{ $item['debit'] > 0 ? \App\Helpers\CurrencyHelper::format($item['debit']) : '-' }}
                    </td>
                    <td class="text-end">
                        {{ $item['credit'] > 0 ? \App\Helpers\CurrencyHelper::format($item['credit']) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">
                        {{ __('messages.no_accounts_with_balances_found') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-light">
                <td colspan="2" class="text-end"><strong>Total</strong></td>
                <td class="text-end">
                    <strong>{{ \App\Helpers\CurrencyHelper::format($totalDebits) }}</strong>
                </td>
                <td class="text-end">
                    <strong>{{ \App\Helpers\CurrencyHelper::format($totalCredits) }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
