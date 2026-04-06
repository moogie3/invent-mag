<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">
                        {{-- Header Card --}}
                        <div class="card-header-custom mb-5 border-bottom pb-4">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="d-flex align-items-center">
                                        <div class="status-indicator {{ $statusClass }}"
                                            style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                                        </div>
                                        <div>
                                            <h2 class="mb-0">{{ __('messages.sr_return_hash') }}{{ $salesReturn->id }}</h2>
                                            <div class="text-muted fs-5">
                                                {{ $salesReturn->sale->customer->name ?? __('messages.not_available') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <span class="badge fs-6 {{ $statusClass }} text-white">
                                        {!! $statusText !!}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Return Information Section --}}
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card bg-body-secondary border-0 h-100 rounded-3 shadow-sm">
                                    <div class="card-body p-3">
                                        <h4 class="card-title mb-3"><i class="ti ti-user me-2 text-primary"></i>{{ __('messages.customer') }}</h4>
                                        <h5 class="mb-2">{{ $salesReturn->sale->customer->name ?? __('messages.not_available') }}</h5>
                                        <div class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>
                                            {{ $salesReturn->sale->customer->address ?? __('messages.not_available') }}
                                        </div>
                                        <div class="text-muted mb-1"><i class="ti ti-phone me-1"></i>
                                            {{ $salesReturn->sale->customer->phone_number ?? __('messages.not_available') }}
                                        </div>
                                        <div class="text-muted mb-1"><i class="ti ti-mail me-1"></i>
                                            {{ $salesReturn->sale->customer->email ?? __('messages.not_available') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-body-secondary border-0 h-100 rounded-3 shadow-sm">
                                    <div class="card-body p-3">
                                        <h4 class="card-title mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('messages.sr_return_information_title') }}</h4>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.sr_return_date') }}</strong></div>
                                            <div>{{ $salesReturn->return_date ? $salesReturn->return_date->format('d F Y') : __('messages.not_available') }}</div>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.sr_original_sale') }}</strong></div>
                                            <div>
                                                @if ($salesReturn->sale)
                                                    <a href="{{ route('admin.sales.view', $salesReturn->sales_id) }}">#{{ $salesReturn->sale->invoice }}</a>
                                                @else
                                                    {{ __('messages.not_available') }}
                                                @endif
                                            </div>
                                        </div>
                                        @if ($salesReturn->reason)
                                            <div class="d-flex justify-content-between mb-2">
                                                <div><strong>{{ __('messages.reason_for_return') }}</strong></div>
                                                <div>{{ $salesReturn->reason }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Returned Items Section --}}
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-body-secondary py-2">
                                <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-primary"></i>{{ __('messages.sr_returned_items_title') }}</h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table card-table table-vcenter table-hover">
                                    <thead class="bg-body-secondary">
                                        <tr>
                                            <th class="text-center" style="width: 60px">{{ __('messages.table_no') }}</th>
                                            <th>{{ __('messages.table_product') }}</th>
                                            <th class="text-center" style="width: 100px">{{ __('messages.table_qty') }}</th>
                                            <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                                            <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($salesReturn->items as $index => $item)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="strong">{{ $item->product->name ?? __('messages.not_available') }}</div>
                                                    @if (isset($item->product->sku) && $item->product->sku)
                                                        <small class="text-muted">{{ __('messages.table_sku') }} {{ $item->product->sku ?? __('messages.not_available') }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">
                                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}
                                                </td>
                                                <td class="text-end">
                                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->total) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-body-secondary">
                                <div class="d-flex justify-content-end">
                                    <div class="text-end">
                                        <div class="fs-5"><strong>{{ __('messages.sr_total_return_amount') }}</strong></div>
                                        <div class="fs-3 fw-bold text-primary">
                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($salesReturn->total_amount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Credit Note Section --}}
                        @if ($salesReturn->customer_credit_id && $salesReturn->credit)
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-body-secondary py-2">
                                <h4 class="card-title mb-0"><i class="ti ti-credit-card me-2 text-success"></i>{{ __('messages.credit_note') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="text-muted small">{{ __('messages.credit_amount') }}</div>
                                        <div class="fs-5 fw-bold text-success">
                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($salesReturn->credit->amount) }}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted small">{{ __('messages.credit_used') }}</div>
                                        <div class="fs-5">
                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($salesReturn->credit->used_amount) }}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted small">{{ __('messages.credit_available') }}</div>
                                        <div class="fs-5 fw-bold text-primary">
                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($salesReturn->credit->available_amount) }}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted small">{{ __('messages.credit_status') }}</div>
                                        <div>
                                            @if ($salesReturn->credit->status === 'active')
                                                <span class="badge bg-success">{{ __('messages.credit_active') }}</span>
                                            @elseif ($salesReturn->credit->status === 'used')
                                                <span class="badge bg-secondary">{{ __('messages.credit_used_status') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('messages.credit_expired_status') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
