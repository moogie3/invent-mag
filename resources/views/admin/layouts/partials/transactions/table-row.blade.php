<tr>
    <td>
        <input class="form-check-input row-checkbox" type="checkbox" value="{{ $transaction->id ?? '' }}">
    </td>
    <td>
        <span class="avatar avatar-sm {{ $transaction->type == 'sale' ? 'bg-success' : 'bg-info' }}-lt">
            <i class="ti {{ $transaction->type == 'sale' ? 'ti-arrow-up' : 'ti-arrow-down' }}"></i>
        </span>
    </td>
    <td>
        <div class="fw-semibold">{{ $transaction->invoice }}</div>
        <div class="small text-muted">
            {{ $transaction->type == 'sale' ? 'Sales' : 'Purchase' }}
        </div>
    </td>
    <td>
        <div class="fw-semibold">{{ $transaction->customer_supplier }}</div>
        @if (isset($transaction->contact_info) && $transaction->contact_info)
            <div class="small text-muted">{{ $transaction->contact_info }}</div>
        @endif
    </td>
    <td>
        <div class="fw-semibold">
            {{ \Carbon\Carbon::parse($transaction->date)->format('M d, Y') }}
        </div>
        <div class="small text-muted">
            {{ \Carbon\Carbon::parse($transaction->date)->format('h:i A') }}
        </div>
    </td>
    <td class="text-end fw-medium">
        {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
        @if (isset($transaction->due_amount) && $transaction->due_amount > 0)
            <div class="small text-danger">
                Due:
                {{ \App\Helpers\CurrencyHelper::format($transaction->due_amount) }}
            </div>
        @endif
    </td>
    <td class="text-center">
        <span
            class="badge {{ $transaction->status == 'Paid' ? 'bg-success' : ($transaction->status == 'Partial' ? 'bg-warning' : 'bg-danger') }}-lt">
            {{ $transaction->status }}
        </span>
    </td>
    <td>
        @include('admin.layouts.partials.transactions.table-actions', ['transaction' => $transaction])
    </td>
</tr>
