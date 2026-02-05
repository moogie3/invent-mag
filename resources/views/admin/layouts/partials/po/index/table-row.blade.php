<tr class="table-row" data-id="{{ $po->id }}">
    <td>
        <input type="checkbox" class="form-check-input row-checkbox" value="{{ $po->id }}">
    </td>
    <td class="sort-no no-print">{{ $pos->firstItem() + $index }}</td>
    <td class="sort-invoice">{{ $po->invoice }}</td>
    <td class="sort-warehouse">{{ $po->warehouse->name ?? 'N/A' }}</td>
    <td class="sort-supplier">{{ $po->supplier->name }}</td>
    <td class="sort-orderdate">{{ $po->order_date->format('d F Y') }}</td>
    <td class="sort-duedate" data-date="{{ $po->due_date->format('Y-m-d') }}">
        {{ $po->due_date->format('d F Y') }}
    </td>
    <td class="sort-amount" data-amount="{{ $po->total_amount }}">
        {{ \App\Helpers\CurrencyHelper::format($po->total_amount) }}
        <span class="raw-amount" style="display: none;">{{ $po->total_amount }}</span>
    </td>
    <td class="sort-payment no-print">{{ $po->payment_type }}</td>
    <td class="sort-status">
        <span class="{{ \App\Helpers\PurchaseHelper::getStatusClass($po->status, $po->due_date) }}">
            {!! \App\Helpers\PurchaseHelper::getStatusText($po->status, $po->due_date) !!}
        </span>
    </td>
    <td class="no-print" style="text-align:center">
        @include('admin.layouts.partials.po.index.table-actions', ['po' => $po])
    </td>
</tr>
