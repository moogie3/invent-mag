<div class="card shadow-sm">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                @php
                    $statusClass = \App\Helpers\SalesHelper::getStatusClass($sales->status, $sales->due_date);
                @endphp
            </div>
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="status-indicator {{ $statusClass }}"
                        style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;"></div>
                    <div>
                        <h2 class="mb-0">{{ __('messages.sales_modal_invoice_no') }}{{ $sales->invoice }}</h2>
                        <div class="text-muted fs-5">
                            {{ $sales->customer->name }} - {{ $sales->customer->address ?? __('messages.not_available') }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge fs-6 {{ $statusClass }}">
                        {!! \App\Helpers\SalesHelper::getStatusText($sales->status, $sales->due_date) !!}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- Sales Info Section -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <h4 class="card-title mb-3"><i
                                class="ti ti-user me-2 text-info"></i>{{ __('messages.model_customer') }}
                        </h4>
                        <div class="mb-2 fw-bold">{{ $sales->customer->name }}</div>
                        <div class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>
                            {{ $sales->customer->address }}
                        </div>
                        <div class="text-muted mb-1"><i class="ti ti-phone me-1"></i>
                            {{ $sales->customer->phone_number }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <h4 class="card-title mb-3"><i
                                class="ti ti-calendar-event me-2 text-info"></i>{{ __('messages.sales_order_information_title') }}
                        </h4>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.sales_order_date') }}</strong></div>
                            <div>{{ $sales->order_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.sales_due_date') }}</strong></div>
                            <div>{{ $sales->due_date?->format('d F Y') ?? '-' }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.sales_payment_type') }}</strong></div>
                            <div>{{ $sales->payment_type ?? __('messages.not_available') }}</div>
                        </div>
                        @if ($sales->status === 'Paid' && $sales->payments->isNotEmpty())
                            <div class="d-flex justify-content-between mb-2">
                                <div><strong>{{ __('messages.payment_date') }}</strong></div>
                                <div>
                                    {{ $sales->payments->last()->payment_date->format('d F Y H:i') }}
                                </div>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.total_paid') }}</strong></div>
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->total_paid) }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>{{ __('messages.balance') }}</strong></div>
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->balance) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card border mb-4">
            <div class="card-header bg-body-secondary py-2">
                <h4 class="card-title mb-0">
                    <i class="ti ti-list me-2 text-info"></i>{{ __('messages.sales_order_items_title') }}
                </h4>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter table-hover">
                    <thead class="bg-body-secondary">
                        <tr>
                            <th class="text-center" style="width: 60px">{{ __('messages.no') }}</th>
                            <th class="text-start">{{ __('messages.table_product') }}</th>
                            <th class="text-center" style="width: 100px">{{ __('messages.table_qty') }}</th>
                            <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                            <th class="text-end" style="width: 140px">{{ __('messages.table_discount') }}</th>
                            <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Calculate invoice summary accounting for returns
                            $summary = \App\Helpers\SalesHelper::calculateInvoiceSummaryWithReturns(
                                $sales->salesItems,
                                $sales->order_discount ?? 0,
                                $sales->order_discount_type ?? 'fixed',
                                $sales->tax_rate ?? 0,
                                $sales->relationLoaded('salesReturns') ? $sales->salesReturns : collect(),
                                $sales->tax_type ?? 'exclusive'
                            );
                        @endphp

                        @foreach ($sales->salesItems as $index => $item)
                            @php
                                // Calculate returned quantity for this item
                                $returnedQty = $sales->getReturnedQuantityForSalesItem($item->id);
                                $adjustedQty = max(0, (int) $item->quantity - $returnedQty);
                                
                                // Calculate the final amount based on adjusted quantity
                                $finalAmount = \App\Helpers\SalesHelper::calculateTotal(
                                    $item->customer_price,
                                    $adjustedQty,
                                    $item->discount,
                                    $item->discount_type,
                                );
                            @endphp

                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="strong">{{ $item->product->name ?? __('messages.not_available') }}</div>
                                    @if (isset($item->product->sku) && $item->product->sku)
                                        <small class="text-muted">{{ __('messages.table_sku') }}
                                            {{ $item->product->sku }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($returnedQty > 0)
                                        <span class="fw-bold">{{ $adjustedQty }}</span>
                                        <br><small class="text-danger"><i class="ti ti-package-export me-1"></i>(-{{ $returnedQty }} {{ __('messages.returned') }})</small>
                                    @else
                                        {{ $item->quantity }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->customer_price) }}
                                </td>
                                <td class="text-end">
                                    @if ($item->discount > 0)
                                        <span class="text-danger">
                                            {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalAmount) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="row">
            <div class="col-md-6">
                <div class="card border-0">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-3"><i
                                class="ti ti-info-circle me-2 text-info"></i>{{ __('messages.sales_order_summary_title') }}
                        </h5>
                        <div class="mb-2">
                            <i class="ti ti-package me-1"></i> {{ __('messages.sales_total_items') }} <strong>{{ $summary['itemCount'] }}</strong>
                        </div>
                        <div class="mb-2">
                            <i class="ti ti-receipt me-1"></i> {{ __('messages.sales_payment_type') }} <strong>{{ $sales->payment_type ?? __('messages.not_available') }}</strong>
                        </div>
                        @if ($sales->notes)
                            <div class="mt-3">
                                <h6>{{ __('messages.sales_notes') }}</h6>
                                <p class="text-muted">{{ $sales->notes }}</p>
                            </div>
                        @endif
                        @if ($sales->terms_conditions)
                            <div class="mt-3">
                                <h6>{{ __('messages.terms_conditions') }}</h6>
                                <p class="text-muted">{{ $sales->terms_conditions }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body p-3">
                        <h5 class="mb-3 card-title">{{ __('messages.sales_amount_summary_title') }}</h5>
                        @php
                            $totalReturned = $sales->getTotalReturnedAmount();
                            $adjustedBalance = $sales->balance;
                        @endphp

                        <div class="d-flex justify-content-between mb-2">
                            <div>{{ __('messages.sales_subtotal') }}</div>
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['subtotal']) }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                {{ __('messages.sales_order_discount') }}
                                <small class="text-muted">
                                    ({{ ($sales->order_discount_type ?? 'fixed') === 'percentage' ? ($sales->order_discount ?? 0) . '%' : __('messages.sales_fixed') }})
                                </small>
                            </div>
                            <div class="text-danger">-
                                {{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['orderDiscount']) }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                {{ __('messages.tax') }}
                                <small class="text-muted">
                                    ({{ $sales->tax_rate ?? 0 }}%)
                                </small>
                            </div>
                            <div>
                                {{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['taxAmount']) }}
                            </div>
                        </div>
                        <hr>
                        
                        @php
                            $adjustedBalance = $sales->balance;
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div><strong>{{ __('messages.sales_grand_total') }}</strong></div>
                            <div class="fw-bold text-primary fs-5">
                                {{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['finalTotal']) }}</div>
                        </div>

                        @php
                            $totalReturned = $sales->getTotalReturnedAmount();
                        @endphp

                        @if($totalReturned > 0)
                        <div class="d-flex justify-content-between text-muted mb-2 border-bottom pb-2">
                            <div class="fs-6"><i class="ti ti-package-export me-1"></i>{{ __('messages.returned') }} ({{ __('messages.sales_totals_adjusted') }})</div>
                            <div class="fs-6">{{ \App\Helpers\CurrencyHelper::formatWithPosition($totalReturned) }}</div>
                        </div>
                        @endif

                        @if(($sales['customer_credit_used'] ?? 0) > 0)
                        <div class="d-flex justify-content-between text-info mb-2">
                            <div class="fs-6"><i class="ti ti-credit-card me-1"></i>{{ __('messages.credit_used') }}</div>
                            <div class="fs-6">{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales['customer_credit_used'] ?? 0) }}</div>
                        </div>
                        @endif

                        <div class="d-flex justify-content-between text-success mb-2 mt-2">
                            <div class="fs-6"><i class="ti ti-wallet me-1"></i>{{ __('messages.total_paid') }}</div>
                            <div class="fs-6">{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->total_paid) }}</div>
                        </div>
                        <div class="d-flex justify-content-between text-danger">
                            <div class="fs-6"><i class="ti ti-wallet me-1"></i><strong>{{ __('messages.balance') }}</strong></div>
                            <div class="fs-6"><strong>{{ \App\Helpers\CurrencyHelper::formatWithPosition($adjustedBalance) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
