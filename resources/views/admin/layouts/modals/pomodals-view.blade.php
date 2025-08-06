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
                        <h2 class="mb-0">PO #{{ $pos->invoice }}</h2>
                        <div class="text-muted fs-5">
                            {{ $pos->supplier->code }} - {{ $pos->supplier->location ?? 'N/A' }}
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
                <div class="card bg-light border-0 h-100">
                    <div class="card-body p-3">
                        <h4 class="card-title mb-3"><i class="ti ti-building-store me-2 text-primary"></i>Supplier
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
                <div class="card bg-light border-0 h-100">
                    <div class="card-body p-3">
                        <h4 class="card-title mb-3"><i class="ti ti-calendar-event me-2 text-primary"></i>Order
                            Information</h4>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Order Date:</strong></div>
                            <div>{{ $pos->order_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Due Date:</strong></div>
                            <div>{{ $pos->due_date->format('d F Y') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Payment Type:</strong></div>
                            <div>{{ $pos->payment_type }}</div>
                        </div>
                        @if ($pos->status === 'Paid')
                            <div class="d-flex justify-content-between">
                                <div><strong>Payment Date:</strong></div>
                                <div>
                                    {{ $pos->payment_date->format('d F Y H:i') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border mb-4">
            <div class="card-header bg-light py-2">
                <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-primary"></i>Order Items</h4>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter table-hover">
                    <thead class="bg-light">
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
                                    <div class="strong">{{ $item->product->name ?? 'N/A' }}</div>
                                    @if (isset($item->product->sku) && $item->product->sku)
                                        <small class="text-muted">SKU: {{ $item->product->sku ?? 'N/A' }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">
                                    {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}
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

        <div class="row">
            <div class="col-md-6">
                <div class="card border-0 bg-light">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>Order Summary
                        </h5>
                        <div class="mb-2">
                            <i class="ti ti-package me-1"></i> Total Items: <strong>{{ $itemCount }}</strong>
                        </div>
                        <div class="mb-2">
                            <i class="ti ti-receipt me-1"></i> Payment Type:
                            <strong>{{ $pos->payment_type }}</strong>
                        </div>
                        @if (property_exists($pos, 'notes') && $pos->notes)
                            <div class="mt-3">
                                <h6>Notes:</h6>
                                <p class="text-muted">{{ $pos->notes }}</p>
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
                            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($subtotal) }}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                Order Discount
                                <small class="text-muted">
                                    ({{ $pos->discount_total_type === 'percentage' ? $pos->discount_total . '%' : 'Fixed' }})
                                </small>:
                            </div>
                            <div class="text-danger">- {{ \App\Helpers\CurrencyHelper::formatWithPosition($orderDiscount) }}
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fs-5"><strong>Grand Total:</strong></div>
                            <div class="fs-3 fw-bold text-primary">
                                {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalTotal) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
