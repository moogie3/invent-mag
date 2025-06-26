<tr class="table-row" data-id="{{ $sale->id }}">
    <td>
        <input type="checkbox" class="form-check-input row-checkbox" value="{{ $sale->id }}">
    </td>
    <td class="sort-no no-print">{{ $sales->firstItem() + $index }}</td>
    <td class="sort-invoice">{{ $sale->invoice }}</td>
    <td class="sort-customer">{{ $sale->customer->name }}</td>
    <td class="sort-orderdate">{{ $sale->order_date->format('d F Y') }}</td>
    <td class="sort-duedate" data-date="{{ $sale->due_date->format('Y-m-d') }}">
        {{ $sale->due_date->format('d F Y') }}
    </td>
    <td class="sort-tax">
        @if ($sale->tax_rate)
            <span class="badge bg-black-lt">Tax {{ $sale->tax_rate }}%</span>
        @else
            <span class="badge bg-black-lt">Not Applied</span>
        @endif
    </td>
    <td class="sort-amount" data-amount="{{ $sale->total }}">
        {{ \App\Helpers\CurrencyHelper::format($sale->total) }}
        <span class="raw-amount" style="display: none;">{{ $sale->total }}</span>
    </td>
    <td class="sort-payment no-print">{{ $sale->payment_type }}</td>
    <td class="sort-status">
        <span class="{{ \App\Helpers\SalesHelper::getStatusClass($sale->status, $sale->due_date) }}">
            {!! \App\Helpers\SalesHelper::getStatusText($sale->status, $sale->due_date) !!}
        </span>
    </td>
    <td class="no-print" style="text-align:center">
        @include('admin.layouts.partials.sales.index.table-actions', ['sale' => $sale])
    </td>
</tr>
