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
                        <h2 class="mb-0">Invoice #{{ $sales->invoice }}</h2>
                        <div class="text-muted fs-5">
                            {{ $sales->customer->name }} - {{ $sales->customer->address ?? 'N/A' }}
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
                        <h4 class="card-title mb-3"><i class="ti ti-user me-2 text-info"></i>Customer
                        </h4>
                        <h5 class="mb-2">{{ $sales->customer->name }}</h5>
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
                        <h4 class="card-title mb-3"><i class="ti ti-calendar-event me-2 text-info"></i>Order
                            Information</h4>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Order Date:</strong></div>
                            <div>{{ $sales->order_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Due Date:</strong></div>
                            <div>{{ $sales->due_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Payment Type:</strong></div>
                            <div>{{ $sales->payment_type ?? 'N/A' }}</div>
                        </div>
                        @if ($sales->status === 'Paid')
                            <div class="d-flex justify-content-between">
                                <div><strong>Payment Date:</strong></div>
                                <div>
                                    {{ $sales->payment_date->setTimezone(auth()->user()->timezone ?? 'UTC')->format('d F Y H:i') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card border mb-4">
            <div class="card-header py-2">
                <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-info"></i>Order Items</h4>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter table-hover">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px">No</th>
                            <th>Product</th>
                            <th class="text-center" style="width: 100px">QTY</th>
                            <th class="text-end" style="width: 140px">Price</th>
                            <th class="text-end" style="width: 140px">Discount</th>
                            <th class="text-end" style="width: 140px">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Calculate invoice summary
                            $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary(
                                $sales->items,
                                $sales->order_discount ?? 0,
                                $sales->order_discount_type ?? 'percentage',
                                $sales->tax_rate ?? 0,
                            );
                        @endphp

                        @foreach ($sales->items as $index => $item)
                            @php
                                // Calculate the final amount for this item
                                $finalAmount = \App\Helpers\SalesHelper::calculateTotal(
                                    $item->customer_price,
                                    $item->quantity,
                                    $item->discount,
                                    $item->discount_type,
                                );
                            @endphp

                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="strong">{{ $item->product->name }}</div>
                                    @if (isset($item->product->sku) && $item->product->sku)
                                        <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">
                                    {{ \App\Helpers\CurrencyHelper::format($item->customer_price) }}
                                </td>
                                <td class="text-end">
                                    @if ($item->discount > 0)
                                        <span class="text-danger">
                                            {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::format($item->discount) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ \App\Helpers\CurrencyHelper::format($finalAmount) }}
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
                        <h5 class="card-title mb-3"><i class="ti ti-info-circle me-2 text-info"></i>Order Summary
                        </h5>
                        <div class="mb-2">
                            <i class="ti ti-package me-1"></i> Total Items:
                            <strong>{{ $summary['itemCount'] }}</strong>
                        </div>
                        <div class="mb-2">
                            <i class="ti ti-receipt me-1"></i> Payment Type:
                            <strong>{{ $sales->payment_type ?? 'N/A' }}</strong>
                        </div>
                        @if (property_exists($sales, 'notes') && $sales->notes)
                            <div class="mt-3">
                                <h6>Notes:</h6>
                                <p class="text-muted">{{ $sales->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body p-3">
                        <h5 class="mb-3 card-title">Amount Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <div>Subtotal:</div>
                            <div>{{ \App\Helpers\CurrencyHelper::format($summary['subtotal']) }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                Order Discount
                                <small class="text-muted">
                                    ({{ ($sales->order_discount_type ?? 'fixed') === 'percentage' ? ($sales->order_discount ?? 0) . '%' : 'Fixed' }})
                                </small>:
                            </div>
                            <div class="text-danger">-
                                {{ \App\Helpers\CurrencyHelper::format($summary['orderDiscount']) }}
                            </div>
                        </div>
                        @if (($sales->tax_rate ?? 0) > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    Tax
                                    <small class="text-muted">
                                        ({{ $sales->tax_rate }}%)
                                    </small>:
                                </div>
                                <div>
                                    {{ \App\Helpers\CurrencyHelper::format($summary['taxAmount']) }}
                                </div>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fs-5"><strong>Grand Total:</strong></div>
                            <div class="fs-3 fw-bold text-primary">
                                {{ \App\Helpers\CurrencyHelper::format($summary['finalTotal']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
