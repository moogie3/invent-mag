<div class="card shadow-sm">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                @php
                    $statusClass = \App\Helpers\PurchaseHelper::getStatusClass($pos->status, $pos->due_date);
                @endphp
            </div>
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="status-indicator {{ $statusClass }}"
                        style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;"></div>
                    <div>
                        <h2 class="mb-0">{{ __('messages.po_hash') }}{{ $pos->invoice }}</h2>
                        <div class="text-muted fs-5">
                            {{ $pos->supplier->code }} - {{ $pos->supplier->location ?? __('messages.not_available') }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge fs-6 {{ $statusClass }}">
                        {!! \App\Helpers\PurchaseHelper::getStatusText($pos->status, $pos->due_date) !!}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <h4 class="card-title mb-3"><i class="ti ti-building-store me-2 text-info"></i>{{ __('messages.supplier_title') }}
                        </h4>
                        <h5 class="mb-2">{{ $pos->supplier->name }}</h5>
                        <div class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>
                            {{ $pos->supplier->address }}
                        </div>
                        <div class="text-muted mb-1"><i class="ti ti-phone me-1"></i>
                            {{ $pos->supplier->phone_number }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <h4 class="card-title mb-3"><i class="ti ti-calendar-event me-2 text-info"></i>{{ __('messages.po_order_information_title') }}
                            </h4>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.po_order_date') }}</strong></div>
                            <div>{{ $pos->order_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.po_due_date') }}</strong></div>
                            <div>{{ $pos->due_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.po_payment_type') }}</strong></div>
                            <div>{{ $pos->payment_type }}</div>
                        </div>
                        @if ($pos->status === 'Paid' && $pos->payments->isNotEmpty())
                            <div class="d-flex justify-content-between mb-2">
                                <div><strong>{{ __('messages.payment_date') }}</strong></div>
                                <div>
                                    {{ $pos->payments->last()->payment_date->format('d F Y H:i') }}
                                </div>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.total_paid') }}</strong></div>
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($pos->total_paid) }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.balance') }}</strong></div>
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($pos->balance) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border mb-4">
            <div class="card-header bg-light py-2">
                <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-info"></i>{{ __('messages.po_order_items_title') }}</h4>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width: 60px">{{ __('messages.table_no') }}</th>
                            <th>{{ __('messages.table_product') }}</th>
                            <th class="text-center" style="width: 100px">{{ __('messages.table_qty') }}</th>
                            <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                            <th class="text-end" style="width: 140px">{{ __('messages.table_discount') }}</th>
                            <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                            <th class="text-center" style="width: 140px">{{ __('messages.table_expiry_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $summary = \App\Helpers\PurchaseHelper::calculateInvoiceSummary(
                                $pos->items,
                                $pos->discount_total,
                                $pos->discount_total_type,
                            );

                            $subtotal = $summary['subtotal'];
                            $itemCount = $summary['itemCount'];
                            $totalProductDiscount = $summary['totalProductDiscount'];
                            $orderDiscount = $summary['orderDiscount'];
                            $finalTotal = $summary['finalTotal'];
                        @endphp

                        @foreach ($pos->items as $index => $item)
                            @php
                                $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal(
                                    $item->price,
                                    $item->quantity,
                                    $item->discount,
                                    $item->discount_type,
                                );

                                $discountPerUnit = \App\Helpers\PurchaseHelper::calculateDiscountPerUnit(
                                    $item->price,
                                    $item->discount,
                                    $item->discount_type,
                                );
                            @endphp

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
                                    @if ($item->discount > 0)
                                        <span class="text-danger">
                                            {{ $item->discount_type === __('messages.percentage') ? $item->discount . '%' : \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                        </td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalAmount) }}
                        </td>
                                <td class="text-center">
                                    @if ($item->expiry_date)
                                        {{ \Carbon\Carbon::parse($item->expiry_date)->format('d M Y') }}
                                    @else
                                        {{ __('messages.not_available') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card border-0">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-3"><i class="ti ti-info-circle me-2 text-info"></i>{{ __('messages.po_order_summary_title') }}
                        </h5>
                        <div class="mb-2">
                            <i class="ti ti-package me-1"></i> {{ __('messages.po_total_items') }} <strong>{{ $itemCount }}</strong>
                        </div>
                        <div class="mb-2">
                            <i class="ti ti-receipt me-1"></i> {{ __('messages.po_payment_type') }}
                            <strong>{{ $pos->payment_type }}</strong>
                        </div>
                        @if (property_exists($pos, 'notes') && $pos->notes)
                            <div class="mt-3">
                                <h6>{{ __('messages.po_notes') }}</h6>
                                <p class="text-muted">{{ $pos->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body p-3">
                        <h5 class="mb-3 card-title">{{ __('messages.po_amount_summary_title') }}</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <div>{{ __('messages.po_subtotal') }}</div>
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($subtotal) }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                {{ __('messages.po_order_discount') }}
                                <small class="text-muted">
                                    ({{ $pos->discount_total_type === __('messages.percentage') ? $pos->discount_total . '%' : __('messages.po_fixed') }})
                                </small>:
                            </div>
                            <div class="text-danger">- {{ \App\Helpers\CurrencyHelper::formatWithPosition($orderDiscount) }}
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fs-5"><strong>{{ __('messages.po_grand_total') }}</strong></div>
                            <div class="fs-3 fw-bold text-primary">
                                {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalTotal) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
