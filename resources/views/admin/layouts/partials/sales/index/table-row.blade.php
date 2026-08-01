<tr class="table-row" data-id="{{ $sale->id }}">
    <td>
        <input type="checkbox" class="form-check-input row-checkbox" value="{{ $sale->id }}">
    </td>
    <td class="sort-no no-print">{{ $sales->firstItem() + $index }}</td>
    <td class="sort-invoice">{{ $sale->invoice }}</td>
    <td class="sort-warehouse">{{ $sale->warehouse->name ?? 'N/A' }}</td>
    <td class="sort-customer">{{ $sale->customer->name }}</td>
    <td class="sort-orderdate">{{ $sale->order_date->format('d F Y') }}</td>
    <td class="sort-duedate" data-date="{{ $sale->due_date?->format('Y-m-d') ?? '-' }}">
        {{ $sale->due_date?->format('d F Y') ?? '-' }}
    </td>
    <td class="sort-tax">
        @if ($sale->tax_rate)
            <span class="badge bg-black-lt">Tax {{ $sale->tax_rate }}%</span>
        @else
            <span class="badge bg-black-lt">{{ __('messages.not_applied') }}</span>
        @endif
    </td>
    <td class="sort-amount" data-amount="{{ $sale->total }}">
        @php
            $totalReturned = $sale->getTotalReturnedAmount();
            $adjustedTotal = $sale->getAdjustedTotal();
            $creditUsed = (float) ($sale['customer_credit_used'] ?? 0);
            $amountAfterCredit = max(0, $adjustedTotal - $creditUsed);
        @endphp
        {{ \App\Helpers\CurrencyHelper::format($amountAfterCredit) }}
        @if($creditUsed > 0)
            <span class="badge bg-info-lt ms-1" title="{{ __('messages.credit_used') }}: {{ \App\Helpers\CurrencyHelper::format($creditUsed) }}">
                <i class="ti ti-credit-card"></i>
            </span>
        @endif
        @if($totalReturned > 0)
            <span class="badge bg-warning-lt ms-1" title="{{ __('messages.returned') }}: {{ \App\Helpers\CurrencyHelper::format($totalReturned) }}">
                <i class="ti ti-package-export"></i>
            </span>
        @endif
        <span class="raw-amount" style="display: none;">{{ $amountAfterCredit }}</span>
    </td>
    <td class="sort-payment no-print">{{ $sale->payment_type }}</td>
    <td class="sort-status">
        <span class="{{ \App\Helpers\SalesHelper::getStatusClass($sale->status, $sale->due_date) }}">
            {!! \App\Helpers\SalesHelper::getStatusText($sale->status, $sale->due_date) !!}
        </span>
    </td>
    @php
        $currentTenant = app('currentTenant');
        $showPpnCol = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
    @endphp
    @if($showPpnCol)
    <td class="sort-ppn">
        @if($sale->fakturPajak)
            <span class="badge bg-{{ $sale->fakturPajak->statusColor() }}">
                <i class="ti ti-file-invoice"></i> {{ $sale->fakturPajak->statusLabel() }}
            </span>
        @elseif($sale->ppn_enabled)
            <span class="badge bg-warning-lt">
                <i class="ti ti-alert-triangle"></i> {{ __('messages.efaktur_not_issued') }}
            </span>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    @endif
    @if($showPpnCol)
    <td>
        @if($sale->isPph())
            <span class="badge bg-orange-lt">{{ __('messages.withholding_tax') }} {{ $sale->pph_rate }}%</span>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    @endif
    <td class="no-print" style="text-align:center">
        @include('admin.layouts.partials.sales.index.table-actions', ['sale' => $sale])
    </td>
</tr>
