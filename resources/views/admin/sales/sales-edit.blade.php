@extends('admin.layouts.base')

@section('title', 'Sales Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title no-print">Edit Sales Order</h2>
                    </div>
                    <div class="col text-end">
                        <button type="submit" form="edit-sales-form" class="btn btn-success">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <!-- Sales Header with colored status bar -->
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        @php
                                            $statusClass = \App\Helpers\SalesHelper::getStatusClass(
                                                $sales->status,
                                                $sales->due_date,
                                            );
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <div class="status-indicator {{ $statusClass }}"
                                                style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                                            </div>
                                            <div>
                                                <h2 class="mb-0">SALE #{{ $sales->invoice }}</h2>
                                                <div class="text-muted fs-5">{{ $sales->customer->address }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge fs-5 p-2 {{ $statusClass }}">
                                            {!! \App\Helpers\SalesHelper::getStatusText($sales->status, $sales->due_date) !!}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <form id="edit-sales-form" enctype="multipart/form-data" method="POST"
                                action="{{ route('admin.sales.update', $sales->id) }}">
                                @csrf
                                @method('PUT')

                                <div class="card-body p-4">
                                    <!-- Sales Info Section -->
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-6">
                                            <div class="card border-0 h-100">
                                                <div class="card-body p-3">
                                                    <h4 class="card-title mb-3"><i
                                                            class="ti ti-users me-2 text-primary"></i>Customer</h4>
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
                                                    <h4 class="card-title mb-3"><i
                                                            class="ti ti-calendar-event me-2 text-primary"></i>Order
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
                                                        <div>
                                                            <select class="form-select form-select-sm" name="payment_type"
                                                                id="payment_type"
                                                                {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                                                                <option value="Cash"
                                                                    {{ $sales->payment_type == 'Cash' ? 'selected' : '' }}>
                                                                    Cash
                                                                </option>
                                                                <option value="Transfer"
                                                                    {{ $sales->payment_type == 'Transfer' ? 'selected' : '' }}>
                                                                    Transfer
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between">
                                                        <div><strong>Payment Status:</strong></div>
                                                        <div>
                                                            <select class="form-select form-select-sm" name="status"
                                                                id="status"
                                                                {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                                                                <option value="Paid"
                                                                    {{ $sales->status == 'Paid' ? 'selected' : '' }}>
                                                                    Paid
                                                                </option>
                                                                <option value="Unpaid"
                                                                    {{ $sales->status == 'Unpaid' ? 'selected' : '' }}>
                                                                    Unpaid
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    @if ($sales->status === 'Paid' && isset($sales->payment_date))
                                                        <div class="d-flex justify-content-between mt-2">
                                                            <div><strong>Payment Date:</strong></div>
                                                            <div>
                                                                {{ $sales->payment_date->format('d F Y H:i') }}
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
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <h4 class="card-title mb-0"><i
                                                            class="ti ti-list me-2 text-primary"></i>Order Items</h4>
                                                </div>
                                                <div class="col-auto">
                                                    <small class="text-muted">
                                                        Select <strong>%</strong> for percentage or <strong>Rp</strong> for
                                                        fixed discount
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width: 60px">No</th>
                                                        <th>Product</th>
                                                        <th class="text-center" style="width: 100px">QTY</th>
                                                        <th class="text-end" style="width: 140px">Price</th>
                                                        <th class="text-end" style="width: 160px">Discount</th>
                                                        <th class="text-end" style="width: 140px">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sales->items as $index => $item)
                                                        @php
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
                                                                    <small class="text-muted">SKU:
                                                                        {{ $item->product->sku }}</small>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                    name="items[{{ $item->id }}][quantity]"
                                                                    value="{{ $item->quantity }}"
                                                                    class="form-control text-end quantity-input"
                                                                    data-item-id="{{ $item->id }}" min="1" />
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                    name="items[{{ $item->id }}][price]"
                                                                    value="{{ intval($item->customer_price) }}"
                                                                    class="form-control text-end price-input"
                                                                    data-item-id="{{ $item->id }}" step="1"
                                                                    min="0" />
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="number"
                                                                        name="items[{{ $item->id }}][discount]"
                                                                        value="{{ (float) $item->discount }}"
                                                                        class="form-control text-end discount-input"
                                                                        style="min-width: 80px;" step="1"
                                                                        min="0"
                                                                        data-item-id="{{ $item->id }}" />

                                                                    <select
                                                                        name="items[{{ $item->id }}][discount_type]"
                                                                        class="form-select discount-type-input"
                                                                        style="min-width: 70px;"
                                                                        data-item-id="{{ $item->id }}">
                                                                        <option value="percentage"
                                                                            {{ $item->discount_type === 'percentage' ? 'selected' : '' }}>
                                                                            %</option>
                                                                        <option value="fixed"
                                                                            {{ $item->discount_type === 'fixed' ? 'selected' : '' }}>
                                                                            Rp</option>
                                                                    </select>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="items[{{ $item->id }}][amount]"
                                                                    value="{{ intval($finalAmount) }}"
                                                                    class="form-control text-end amount-input"
                                                                    data-item-id="{{ $item->id }}" readonly />
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
                                                            class="ti ti-info-circle me-2 text-primary"></i>Order Summary
                                                    </h5>

                                                    @php
                                                        // Use the helper to calculate summary info
                                                        $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary(
                                                            $sales->items,
                                                            $sales->order_discount ?? 0,
                                                            $sales->order_discount_type ?? 'percentage',
                                                            $sales->tax_rate ?? 0,
                                                        );
                                                    @endphp

                                                    <div class="mb-2">
                                                        <i class="ti ti-package me-1"></i> Total Items:
                                                        <strong>{{ $summary['itemCount'] }}</strong>
                                                    </div>
                                                    <div class="mb-2">
                                                        <i class="ti ti-receipt me-1"></i> Payment Type:
                                                        <strong>{{ $sales->payment_type }}</strong>
                                                    </div>
                                                    @if (property_exists($sales, 'notes') || isset($sales->notes))
                                                        <div class="mt-3">
                                                            <h6><i class="ti ti-notes me-1"></i> Notes:</h6>
                                                            <textarea name="notes" class="form-control" rows="3">{{ $sales->notes ?? '' }}</textarea>
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
                                                        <div id="subtotal">
                                                            {{ number_format($summary['subtotal'], 0, ',', '.') }}
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <div>
                                                            <span>Order Discount:</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="input-group me-2" style="width: 200px;">
                                                                <input type="number" name="order_discount"
                                                                    value="{{ (float) ($sales->order_discount ?? 0) }}"
                                                                    class="form-control text-end" id="discountTotalValue"
                                                                    step="1" min="0"
                                                                    style="min-width: 80px;" />

                                                                <select name="order_discount_type" class="form-select"
                                                                    id="discountTotalType" style="min-width: 70px;">
                                                                    <option value="percentage"
                                                                        {{ ($sales->order_discount_type ?? '') === 'percentage' ? 'selected' : '' }}>
                                                                        %</option>
                                                                    <option value="fixed"
                                                                        {{ ($sales->order_discount_type ?? '') === 'fixed' ? 'selected' : '' }}>
                                                                        Rp</option>
                                                                </select>
                                                            </div>
                                                            <div class="text-danger" id="orderDiscountTotal">
                                                                {{ number_format($summary['orderDiscount'], 0, ',', '.') }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if (($sales->tax_rate ?? 0) > 0)
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <div>Tax ({{ $sales->tax_rate }}%):</div>
                                                            <div class="text-muted" id="totalTax">
                                                                {{ number_format($summary['taxAmount'], 0, ',', '.') }}
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <hr>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fs-5"><strong>Grand Total:</strong></div>
                                                        <div class="fs-3 fw-bold text-primary" id="finalTotal">
                                                            {{ number_format($summary['finalTotal'], 0, ',', '.') }}
                                                        </div>
                                                    </div>

                                                    <!-- Hidden inputs for form submission -->
                                                    <input type="hidden" id="grandTotalInput" name="total"
                                                        value="{{ $summary['finalTotal'] }}">
                                                    <input type="hidden" id="taxInput" name="tax_amount"
                                                        value="{{ $summary['taxAmount'] }}">
                                                    <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                        value="{{ $summary['orderDiscount'] }}">
                                                    <input type="hidden" id="taxRateInput" name="tax_rate"
                                                        value="{{ $sales->tax_rate ?? 0 }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.salesmodals')
@endsection
