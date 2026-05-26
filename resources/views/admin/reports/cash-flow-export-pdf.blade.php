<!DOCTYPE html>
<html>
<head>
    <title>Cash Flow Statement</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3, h4 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .net-income { margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; }
        .ps-4 { padding-left: 30px; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
    </style>
</head>
<body>
    <h1>Cash Flow Statement</h1>
    <h2>For the Period {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}</h2>

    <div class="report-section">
        <h4>Operating Activities</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fw-bold">
                    <td>Net Income</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($net_income) }}</td>
                </tr>
                @if(count($non_cash_items) > 0)
                    <tr class="fw-bold">
                        <td colspan="2">Non-Cash Items</td>
                    </tr>
                    @foreach($non_cash_items as $item)
                        <tr>
                            <td class="ps-4">{{ __($item['account_name']) }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total Non-Cash Adjustments</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($non_cash_total) }}</td>
                    </tr>
                @endif
                @if(count($working_capital_changes) > 0)
                    <tr class="fw-bold">
                        <td colspan="2">Changes in Working Capital</td>
                    </tr>
                    @foreach($working_capital_changes as $item)
                        <tr>
                            <td class="ps-4">{{ __($item['account_name']) }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total Working Capital Changes</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($working_capital_total) }}</td>
                    </tr>
                @endif
                <tr class="fw-bold">
                    <td>Net Cash from Operating Activities</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($operating_cash_flow) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section">
        <h4>Investing Activities</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($investing_activities as $item)
                    <tr>
                        <td>{{ __($item['account_name']) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center;color:#999;">No investing activities in this period</td>
                    </tr>
                @endforelse
                <tr class="fw-bold">
                    <td>Net Cash from Investing Activities</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($investing_cash_flow) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section">
        <h4>Financing Activities</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($financing_activities as $item)
                    <tr>
                        <td>{{ __($item['account_name']) }}</td>
                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center;color:#999;">No financing activities in this period</td>
                    </tr>
                @endforelse
                <tr class="fw-bold">
                    <td>Net Cash from Financing Activities</td>
                    <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($financing_cash_flow) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-section net-income">
        <h3 style="display:flex;justify-content:space-between;">
            <span>Net Increase (Decrease) in Cash</span>
            <span style="{{ $net_change_in_cash >= 0 ? 'color:#28a745;' : 'color:#dc3545;' }}">{{ \App\Helpers\CurrencyHelper::format($net_change_in_cash) }}</span>
        </h3>
        <div style="display:flex;justify-content:space-between;margin-top:8px;">
            <span style="color:#666;">Cash at Beginning of Period</span>
            <span>{{ \App\Helpers\CurrencyHelper::format($opening_cash_balance) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:4px;font-weight:bold;">
            <span>Cash at End of Period</span>
            <span>{{ \App\Helpers\CurrencyHelper::format($closing_cash_balance) }}</span>
        </div>
    </div>
</body>
</html>
