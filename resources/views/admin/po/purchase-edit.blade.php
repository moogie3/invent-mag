@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title no-print">Edit PO Invoice</h2>
                    </div>
                    <div class="col text-end">
                        <button type="submit" form="edit-po-form" class="btn btn-success">
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
                            <!-- PO Header with colored status bar -->
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        @php
                                            $statusClass = 'badge bg-blue-lt';
                                            if ($pos->status === 'Paid') {
                                                $statusClass = 'badge bg-green-lt';
                                            } elseif (now()->isAfter($pos->due_date)) {
                                                $statusClass = 'badge bg-red-lt';
                                            } elseif (now()->diffInDays($pos->due_date) <= 7) {
                                                $statusClass = 'badge bg-orange-lt';
                                            }
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <div class="status-indicator {{ $statusClass }}"
                                                style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                                            </div>
                                            <div>
                                                <h2 class="mb-0">PO #{{ $pos->invoice }}</h2>
                                                <div class="text-muted fs-5">{{ $pos->supplier->code }} -
                                                    {{ $pos->supplier->location ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge fs-5 p-2 {{ $statusClass }}">
                                            @php
                                                $today = now();
                                                $dueDate = $pos->due_date;
                                                $diffDays = $today->diffInDays($dueDate, false);

                                                if ($pos->status === 'Paid') {
                                                    echo '<i class="ti ti-check me-1"></i> Paid';
                                                } elseif ($diffDays == 0) {
                                                    echo '<i class="ti ti-alert-triangle me-1"></i> Due Today';
                                                } elseif ($diffDays > 0 && $diffDays <= 3) {
                                                    echo '<i class="ti ti-calendar-event me-1"></i> Due in ' .
                                                        $diffDays .
                                                        ' Days';
                                                } elseif ($diffDays > 3 && $diffDays <= 7) {
                                                    echo '<i class="ti ti-calendar me-1"></i> Due in 1 Week';
                                                } elseif ($diffDays < 0) {
                                                    echo '<i class="ti ti-alert-circle me-1"></i> Overdue';
                                                } else {
                                                    echo '<i class="ti ti-clock me-1"></i> Pending';
                                                }
                                            @endphp
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <form id="edit-po-form" enctype="multipart/form-data" method="POST"
                                action="{{ route('admin.po.update', $pos->id) }}">
                                @csrf
                                @method('PUT')

                                <div class="card-body p-4">
                                    <!-- PO Info Section -->
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-6">
                                            <div class="card bg-light border-0 h-100">
                                                <div class="card-body p-3">
                                                    <h4 class="card-title mb-3"><i
                                                            class="ti ti-building-store me-2 text-primary"></i>Supplier</h4>
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
                                                    <h4 class="card-title mb-3"><i
                                                            class="ti ti-calendar-event me-2 text-primary"></i>Order
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
                                                        <div>
                                                            <select class="form-select form-select-sm" name="payment_type"
                                                                id="payment_type"
                                                                {{ $pos->status == 'Paid' ? 'disabled' : '' }}>
                                                                <option value="Cash"
                                                                    {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>
                                                                    Cash
                                                                </option>
                                                                <option value="Transfer"
                                                                    {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>
                                                                    Transfer
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between">
                                                        <div><strong>Payment Status:</strong></div>
                                                        <div>
                                                            <select class="form-select form-select-sm" name="status"
                                                                id="status">
                                                                <option value="Paid"
                                                                    {{ $pos->status == 'Paid' ? 'selected' : '' }}>
                                                                    Paid
                                                                </option>
                                                                <option value="Unpaid"
                                                                    {{ $pos->status == 'Unpaid' ? 'selected' : '' }}>
                                                                    Unpaid
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    @if ($pos->status === 'Paid' && isset($pos->payment_date))
                                                        <div class="d-flex justify-content-between mt-2">
                                                            <div><strong>Payment Date:</strong></div>
                                                            <div>
                                                                {{ $pos->payment_date->setTimezone(auth()->user()->timezone)->format('d F Y H:i') }}
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items Table -->
                                    <div class="card border mb-4">
                                        <div class="card-header bg-light py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h4 class="card-title mb-0"><i
                                                        class="ti ti-list me-2 text-primary"></i>Order Items</h4>
                                                <small class="text-muted">
                                                    Select <strong>%</strong> for percentage or <strong>Rp</strong> for
                                                    fixed discount
                                                </small>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead class="bg-light">
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
                                                    @foreach ($pos->items as $index => $item)
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
                                                                    value="{{ intval($item->price) }}"
                                                                    class="form-control text-end price-input"
                                                                    data-item-id="{{ $item->id }}" step="0"
                                                                    min="0" />
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="number"
                                                                        name="items[{{ $item->id }}][discount]"
                                                                        value="{{ (float) $item->discount }}"
                                                                        class="form-control text-end discount-input"
                                                                        style="min-width: 80px;" step="0"
                                                                        min="0"
                                                                        data-item-id="{{ $item->id }}" />

                                                                    <select
                                                                        name="items[{{ $item->id }}][discountType]"
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
                                                                    value="{{ intval($item->quantity * $item->price - $item->discount) }}"
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
                                            <div class="card border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <h5 class="card-title mb-3"><i
                                                            class="ti ti-info-circle me-2 text-primary"></i>Order Summary
                                                    </h5>
                                                    <div class="mb-2">
                                                        <i class="ti ti-package me-1"></i> Total Items:
                                                        <strong>{{ $pos->items->count() }}</strong>
                                                    </div>
                                                    <div class="mb-2">
                                                        <i class="ti ti-receipt me-1"></i> Payment Type:
                                                        <strong>{{ $pos->payment_type }}</strong>
                                                    </div>
                                                    @if (property_exists($pos, 'notes'))
                                                        <div class="mt-3">
                                                            <h6><i class="ti ti-notes me-1"></i> Notes:</h6>
                                                            <textarea name="notes" class="form-control" rows="3">{{ $pos->notes ?? '' }}</textarea>
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
                                                        <div id="subtotal">0</div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <div>
                                                            <span>Order Discount:</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="input-group me-2" style="width: 200px;">
                                                                <input type="number" name="discount_total"
                                                                    value="{{ (float) $pos->discount_total }}"
                                                                    class="form-control text-end" id="discountTotalValue"
                                                                    step="0" min="0"
                                                                    style="min-width: 80px;" />

                                                                <select name="discount_total_type" class="form-select"
                                                                    id="discountTotalType" style="min-width: 70px;">
                                                                    <option value="percentage"
                                                                        {{ $pos->discount_total_type === 'percentage' ? 'selected' : '' }}>
                                                                        %</option>
                                                                    <option value="fixed"
                                                                        {{ $pos->discount_total_type === 'fixed' ? 'selected' : '' }}>
                                                                        Rp</option>
                                                                </select>
                                                            </div>
                                                            <div class="text-danger" id="orderDiscountTotal">0</div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fs-5"><strong>Grand Total:</strong></div>
                                                        <div class="fs-3 fw-bold text-primary" id="finalTotal">0</div>
                                                    </div>
                                                    <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                        value="0">
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
@endsection
