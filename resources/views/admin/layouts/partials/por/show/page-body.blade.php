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
                                            <h2 class="mb-0">{{ __('messages.pr_return_hash') }}{{ $por->id }}</h2>
                                            <div class="text-muted fs-5">
                                                {{ $por->purchase->supplier->code ?? __('messages.not_available') }} -
                                                {{ $por->purchase->supplier->name ?? __('messages.not_available') }}
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
                                <div class="card bg-light border-0 h-100 rounded-3 shadow-sm">
                                    <div class="card-body p-3">
                                        <h4 class="card-title mb-3"><i class="ti ti-building me-2 text-primary"></i>{{ __('messages.supplier_title') }}</h4>
                                        <h5 class="mb-2">{{ $por->purchase->supplier->name ?? __('messages.not_available') }}</h5>
                                        <div class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>
                                            {{ $por->purchase->supplier->address ?? __('messages.not_available') }}
                                        </div>
                                        <div class="text-muted mb-1"><i class="ti ti-phone me-1"></i>
                                            {{ $por->purchase->supplier->phone_number ?? __('messages.not_available') }}
                                        </div>
                                        <div class="text-muted mb-1"><i class="ti ti-mail me-1"></i>
                                            {{ $por->purchase->supplier->email ?? __('messages.not_available') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0 h-100 rounded-3 shadow-sm">
                                    <div class="card-body p-3">
                                        <h4 class="card-title mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('messages.pr_return_information_title') }}</h4>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.pr_return_date') }}</strong></div>
                                            <div>{{ $por->return_date ? $por->return_date->format('d F Y') : __('messages.not_available') }}</div>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.pr_original_po') }}</strong></div>
                                            <div>
                                                @if ($por->purchase)
                                                    <a href="{{ route('admin.po.view', $por->purchase_id) }}">#{{ $por->purchase->invoice }}</a>
                                                @else
                                                    {{ __('messages.not_available') }}
                                                @endif
                                            </div>
                                        </div>
                                        @if ($por->reason)
                                            <div class="d-flex justify-content-between mb-2">
                                                <div><strong>{{ __('messages.reason_for_return') }}</strong></div>
                                                <div>{{ $por->reason }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                         {{-- Returned Items Section --}}
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-light py-2">
                                <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-primary"></i>{{ __('messages.pr_returned_items_title') }}</h4>
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
                                        @foreach ($por->items as $index => $item)
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
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-end">
                                    <div class="text-end">
                                        <div class="fs-5"><strong>{{ __('messages.pr_total_return_amount') }}</strong></div>
                                        <div class="fs-3 fw-bold text-primary">
                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($por->total_amount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
