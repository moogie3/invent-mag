@isset($quotation)
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>{{ __('messages.customer') }}</h5>
                <p class="mb-1">{{ $quotation->customer?->name ?? 'N/A' }}</p>
                @if($quotation->customer?->address)
                    <p class="mb-1 text-muted">{{ $quotation->customer->address }}</p>
                @endif
            </div>
            <div class="col-md-6 text-md-end">
                <h5>{{ __('messages.quotation_details') }}</h5>
                <p class="mb-1">{{ $quotation->quotation_number }}</p>
                <p class="mb-1">{{ $quotation->quotation_date?->format('d M Y') }}</p>
                @php
                    $statusBadgeClass = [
                        'draft' => 'bg-secondary-lt',
                        'sent' => 'bg-primary-lt',
                        'accepted' => 'bg-success-lt',
                        'rejected' => 'bg-danger-lt',
                        'expired' => 'bg-warning-lt',
                        'converted' => 'bg-info-lt',
                    ][$quotation->status] ?? 'bg-secondary-lt';
                @endphp
                <span class="badge rounded-pill {{ $statusBadgeClass }}">{{ __("messages.quotation_status_{$quotation->status}") }}</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="text-center">
                    <tr>
                        <th>{{ __('messages.table_no') }}</th>
                        <th>{{ __('messages.table_product') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.table_price') }}</th>
                        <th>{{ __('messages.table_discount') }}</th>
                        <th>{{ __('messages.table_amount') }}</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    @php
                        $items = collect($quotation->items)->map(fn($item) => is_array($item) ? (object) $item : $item);
                    @endphp
                    @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product_name ?? $item->product?->name ?? 'N/A' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 2) }}</td>
                        <td>
                            @if($item->discount > 0)
                                {{ $item->discount_type === 'percentage' ? $item->discount . '%' : number_format($item->discount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ __('messages.quotation_subtotal') }}</span>
                    <strong>{{ number_format($quotation->subtotal, 2) }}</strong>
                </div>
                @if($quotation->discount_amount > 0)
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ __('messages.quotation_discount') }}</span>
                    <strong>-{{ number_format($quotation->discount_amount, 2) }}</strong>
                </div>
                @endif
                @if($quotation->tax_amount > 0)
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ __('messages.quotation_tax') }}</span>
                    <strong>{{ number_format($quotation->tax_amount, 2) }}</strong>
                </div>
                @endif
                <hr class="my-1">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">{{ __('messages.quotation_total') }}</span>
                    <strong class="fs-5">{{ number_format($quotation->total, 2) }}</strong>
                </div>
            </div>
        </div>

        @if($quotation->notes)
        <div class="row mt-3">
            <div class="col-12">
                <h6>{{ __('messages.quotation_notes') }}</h6>
                <p class="text-muted">{{ $quotation->notes }}</p>
            </div>
        </div>
        @endif
        @if($quotation->terms_conditions)
        <div class="row mt-3">
            <div class="col-12">
                <h6>{{ __('messages.terms_conditions') }}</h6>
                <p class="text-muted">{{ $quotation->terms_conditions }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endisset
