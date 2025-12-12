<div class="modal-header">
    <h5 class="modal-title"><i class="ti ti-receipt-refund me-2"></i>{{ __('messages.sales_return_details') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="status-indicator {{ $statusClass }}"
                        style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;"></div>
                    <div>
                        <h2 class="mb-0">{{ __('messages.sr_return_hash') }}{{ $salesReturn->id }}</h2>
                        <div class="text-muted fs-5">
                            {{ $salesReturn->sales->customer->name ?? __('messages.not_available') }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge fs-6 {{ $statusClass }}">
                        {!! $statusText !!}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
                <div class="card-body p-3">
                    <h4 class="card-title mb-3"><i
                            class="ti ti-user me-2 text-primary"></i>{{ __('messages.customer') }}</h4>
                    <h5 class="mb-2">{{ $salesReturn->sales->customer->name ?? __('messages.not_available') }}</h5>
                    <div class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>
                        {{ $salesReturn->sales->customer->address ?? __('messages.not_available') }}
                    </div>
                    <div class="text-muted mb-1"><i class="ti ti-phone me-1"></i>
                        {{ $salesReturn->sales->customer->phone_number ?? __('messages.not_available') }}
                    </div>
                    <div class="text-muted mb-1"><i class="ti ti-mail me-1"></i>
                        {{ $salesReturn->sales->customer->email ?? __('messages.not_available') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
                <div class="card-body p-3">
                    <h4 class="card-title mb-3"><i
                            class="ti ti-info-circle me-2 text-primary"></i>{{ __('messages.sr_return_information_title') }}
                    </h4>
                    <div class="d-flex justify-content-between mb-2">
                        <div><strong>{{ __('messages.sr_return_date') }}</strong></div>
                        <div>{{ $salesReturn->return_date ? $salesReturn->return_date->format('d F Y') : __('messages.not_available') }}</div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <div><strong>{{ __('messages.sr_original_sale') }}</strong></div>
                        <div>
                            @if ($salesReturn->sales)
                                <a href="{{ route('admin.sales.view', $salesReturn->sales_id) }}">#{{ $salesReturn->sales->invoice }}</a>
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

    <div class="card border mb-4">
        <div class="card-header bg-light py-2">
            <h4 class="card-title mb-0"><i
                    class="ti ti-list me-2 text-primary"></i>{{ __('messages.sr_returned_items_title') }}</h4>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter table-hover">
                <thead class="bg-light">
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
                                    <small class="text-muted">{{ __('messages.table_sku') }}
                                        {{ $item->product->sku ?? __('messages.not_available') }}</small>
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
        <div class="card-footer bg-light">
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

</div>
<div class="modal-footer">
    <div class="text-muted me-auto">
        <small><i class="ti ti-info-circle me-1"></i>
            {{ __('messages.sr_modal_details_info_message') }}</small>
    </div>
    <a href="#" class="btn btn-info" id="srModalFullView">
        <i class="ti ti-zoom-scan me-1"></i> {{ __('messages.sr_full_view_button') }}
    </a>
    <button type="button" class="btn btn-secondary" id="srModalPrint">
        <i class="ti ti-printer me-1"></i> {{ __('messages.sr_modal_details_print_button') }}
    </button>
    <a href="#" class="btn btn-primary" id="srModalEdit">
        <i class="ti ti-edit me-1"></i> {{ __('messages.edit') }}
    </a>
</div>
