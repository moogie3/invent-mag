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
        @php
            $totalReturned = $po->getTotalReturnedAmount();
            $adjustedTotal = $po->getAdjustedTotal();
        @endphp
        {{ \App\Helpers\CurrencyHelper::format($adjustedTotal) }}
        @if($totalReturned > 0)
            <span class="badge bg-warning-lt ms-1" title="{{ __('messages.returned') }}: {{ \App\Helpers\CurrencyHelper::format($totalReturned) }}">
                <i class="ti ti-package-import"></i>
            </span>
        @endif
        <span class="raw-amount" style="display: none;">{{ $adjustedTotal }}</span>
    </td>
    <td class="sort-tax">
        @if(($po->tax_rate ?? 0) > 0)
            <span class="text-muted">{{ $po->tax_rate }}% ({{ ucfirst($po->tax_type ?? 'exclusive') }})</span>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    <td class="sort-payment no-print">{{ $po->payment_type }}</td>
    <td class="sort-status">
        <span class="{{ \App\Helpers\PurchaseHelper::getStatusClass($po->status, $po->due_date) }}">
            {!! \App\Helpers\PurchaseHelper::getStatusText($po->status, $po->due_date) !!}
        </span>
    </td>
    @php
        $currentTenant = app('currentTenant');
        $showPpnCol = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
    @endphp
    @if($showPpnCol)
    <td>
        @if($po->isPph())
            <span class="badge bg-orange-lt">{{ __('messages.withholding_tax') }} {{ $po->pph_rate }}%</span>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    @endif
    <td class="no-print" style="text-align:center">
        @include('admin.layouts.partials.po.index.table-actions', ['po' => $po])
    </td>
</tr>
