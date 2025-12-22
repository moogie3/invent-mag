<!DOCTYPE html>
<html>

<head>
    <title>General Journal</title>
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
    <h1>General Journal</h1>
    <p>
        <strong>Start Date:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
        <strong>End Date:</strong> {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Account</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $entry)
                @foreach ($entry->transactions as $index => $transaction)
                    <tr>
                        @if ($index === 0)
                            <td rowspan="{{ $entry->transactions->count() }}" class="align-top">
                                {{ $entry->date->format('d M Y') }}</td>
                            <td rowspan="{{ $entry->transactions->count() }}" class="align-top">
                                {{ $entry->description }}
                            </td>
                        @endif
                        <td style="padding-left: {{ $transaction->type == 'credit' ? '2rem' : '0.5rem' }};">
                            {{ __($transaction->account->name) }}
                        </td>
                        <td class="text-end">
                            @if ($transaction->type == 'debit')
                                {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($transaction->type == 'credit')
                                {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if (!$loop->last)
                    <tr class="table-border-bottom"></tr>
                @endif
            @endforeach
        </tbody>
    </table>
</body>

</html>
