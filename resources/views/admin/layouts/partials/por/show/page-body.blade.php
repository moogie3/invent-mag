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
                                    @php
                                        $statusClass = match($por->status) {
                                            'Completed' => 'bg-success',
                                            'Pending' => 'bg-warning',
                                            'Canceled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $statusText = $por->status;
                                    @endphp
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
                                        {{ $statusText }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Return Information Section --}}
                        <div class="mb-5 border-bottom pb-4">
                            <h4 class="card-title mb-4">
                                <i class="ti ti-receipt-refund me-2 text-primary"></i> {{ __('messages.pr_return_information_title') }}
                            </h4>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="h-100">
                                        <h5 class="card-title mb-3 text-secondary">
                                            <i class="ti ti-building-store me-2 text-muted"></i>{{ __('messages.supplier_title') }}
                                        </h5>
                                        <h4 class="mb-2 fw-bold">{{ $por->purchase->supplier->name ?? __('messages.not_available') }}</h4>
                                        <div class="text-muted mb-1">
                                            <i class="ti ti-map-pin me-1"></i>
                                            {{ $por->purchase->supplier->address ?? __('messages.not_available') }}
                                        </div>
                                        <div class="text-muted mb-1">
                                            <i class="ti ti-phone me-1"></i>
                                            {{ $por->purchase->supplier->phone_number ?? __('messages.not_available') }}
                                        </div>
                                        <div class="text-muted mb-1">
                                            <i class="ti ti-mail me-1"></i>
                                            {{ $por->purchase->supplier->email ?? __('messages.not_available') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="h-100">
                                        <h5 class="card-title mb-3 text-secondary">
                                            <i class="ti ti-info-circle me-2 text-muted"></i>{{ __('messages.pr_return_details_title') }}
                                        </h5>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">{{ __('messages.pr_return_date') }}</label>
                                                    <div class="form-control-plaintext">
                                                        {{ $por->return_date ? $por->return_date->format('d F Y') : __('messages.not_available') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">{{ __('messages.pr_original_po') }}</label>
                                                    <div class="form-control-plaintext">
                                                        @if ($por->purchase)
                                                            <a href="{{ route('admin.po.view', $por->purchase_id) }}" class="fw-bold text-primary">
                                                                #{{ $por->purchase->invoice }}
                                                            </a>
                                                        @else
                                                            {{ __('messages.not_available') }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if ($por->reason)
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">{{ __('messages.reason_for_return') }}</label>
                                                <div class="form-control-plaintext">{{ $por->reason }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Returned Items Section --}}
                        <div class="mb-5 border-bottom pb-4">
                            <div class="row align-items-center mb-4">
                                <div class="col">
                                    <h4 class="card-title mb-0">
                                        <i class="ti ti-box me-2 text-primary"></i>{{ __('messages.pr_returned_items_title') }}
                                    </h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="text-center">
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
                                                    <div class="fw-bold">{{ $item->product->name ?? __('messages.not_available') }}</div>
                                                    @if (isset($item->product->sku) && $item->product->sku)
                                                        <small class="text-muted">{{ __('messages.table_sku') }} {{ $item->product->sku }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">
                                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}
                                                </td>
                                                <td class="text-end fw-bold">
                                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->total) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Total Section --}}
                        <div class="row">
                            <div class="col-md-6 ms-auto">
                                <div class="h-100 p-4 bg-primary-lt rounded-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="fw-semibold mb-1">{{ __('messages.pr_total_return_amount') }}</h4>
                                            <div class="fs-3 fw-bold text-primary">
                                                {{ \App\Helpers\CurrencyHelper::formatWithPosition($por->total_amount) }}
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-3 p-3 shadow-sm">
                                            <i class="ti ti-receipt-refund fs-1 text-primary"></i>
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
