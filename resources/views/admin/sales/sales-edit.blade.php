@extends('admin.layouts.base')

@section('title', 'Sales Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header ">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        @if ($sales->status == 'Unpaid')
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title">Edit Sales Order</h2>
                        @else
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title">Sales Invoice Information</h2>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-body">
                                <form enctype="multipart/form-data" method="POST"
                                    action="{{ route('admin.sales.update', $sales->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <h1 class="text-center">Edit Invoice {{ $sales->invoice }}</h1>
                                    <fieldset class="form-fieldset">
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PAYMENT TYPE</label>
                                                <select class="form-control" name="payment_type" id="payment_type"
                                                    {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                                                    <option value="Cash"
                                                        {{ $sales->payment_type == 'Cash' ? 'selected' : '' }}>
                                                        Cash</option>
                                                    <option value="Transfer"
                                                        {{ $sales->payment_type == 'Transfer' ? 'selected' : '' }}>
                                                        Transfer</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">STATUS</label>
                                                <select class="form-control" name="status" id="status"
                                                    {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                                                    <option value="Paid" {{ $sales->status == 'Paid' ? 'selected' : '' }}>
                                                        Paid
                                                    </option>
                                                    <option value="Unpaid"
                                                        {{ $sales->status == 'Unpaid' ? 'selected' : '' }}>
                                                        Unpaid</option>
                                                </select>
                                            </div>
                                            <div class="col-md-9 mb-3 mt-4 text-end">
                                                <button type="submit" class="btn btn-success">Save</button>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">CUSTOMER</label>
                                            <select class="form-control" name="customer_id" id="customer_id">
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}"
                                                        data-payment-terms="{{ $customer->payment_terms }}"
                                                        {{ $sales->customer_id == $customer->id ? 'selected' : '' }}>
                                                        {{ $customer->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">ORDER DATE</label>
                                            <input type="date" class="form-control" name="order_date" id="order_date"
                                                value="{{ $sales->order_date->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">DUE DATE</label>
                                            <input type="date" class="form-control" name="due_date" id="due_date"
                                                value="{{ $sales->due_date->format('Y-m-d') }}" required readonly>
                                        </div>
                                    </div>

                                    <input type="hidden" id="grandTotalInput" name="total" value="0">
                                    <input type="hidden" id="taxInput" name="tax_amount" value="0">
                                    <input type="hidden" id="totalDiscountInput" name="total_discount" value="0">
                                    <!-- Store the tax rate -->
                                    <input type="hidden" id="taxRateInput" name="tax_rate"
                                        value="{{ $sales->tax_rate ?? 0 }}">

                                    <div class="page-wrapper">
                                        <div class="page-body">
                                            <div class="container-xl">
                                                <div class="card card-lg">
                                                    <div class="card-body">
                                                        <div class="col-auto">
                                                            <h3>SALE / {{ $sales->invoice }} /
                                                                {{ $sales->customer->address }}
                                                            </h3>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <p class="h1">{{ $sales->customer->name }}</p>
                                                                <address>
                                                                    {{ $sales->customer->address }}<br>
                                                                    {{ $sales->customer->phone_number }}<br>
                                                                    Order Date :
                                                                    {{ $sales->order_date->format('d-m-Y') }}
                                                                </address>
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <p class="h3">Payment Status</p>
                                                                <h3>
                                                                    @if ($sales->status !== 'Paid')
                                                                        <span class="badge bg-blue-lt">
                                                                            Payment Pending
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-green-lt">
                                                                            Paid on
                                                                            {{ $sales->payment_date->format('d F Y') }}<br>
                                                                            {{ $sales->payment_date->format('H:i:s') }}
                                                                        </span>
                                                                    @endif
                                                                </h3>
                                                                <address>
                                                                    Due Date :
                                                                    {{ $sales->due_date->format('d-m-Y') }}
                                                                </address>
                                                                <small class="text-muted d-block mb-3 text-end">
                                                                    Select <strong>%</strong> for percentage or
                                                                    <strong>Rp</strong> for fixed discount.
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <table class="table table-transparent table-responsive">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-center" style="width: 1%">No</th>
                                                                    <th>Product</th>
                                                                    <th class="text-center" style="width: 10%">QTY</th>
                                                                    <th class="text-end" style="width: 15%">Price</th>
                                                                    <th class="text-end" style="width: 13%">Unit Discount
                                                                    </th>
                                                                    <th class="text-end" style="width: 20%">Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($sales->items as $index => $item)
                                                                    <tr>
                                                                        <td>{{ $index + 1 }}</td>
                                                                        <td>
                                                                            <p class="strong mb-1">
                                                                                {{ $item->product->name }}</p>
                                                                        </td>
                                                                        <td>
                                                                            <input type="number"
                                                                                name="items[{{ $item->id }}][quantity]"
                                                                                value="{{ $item->quantity }}"
                                                                                class="form-control text-end quantity-input"
                                                                                data-item-id="{{ $item->id }}"
                                                                                min="1" />
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <input type="number"
                                                                                name="items[{{ $item->id }}][price]"
                                                                                value="{{ intval($item->customer_price) }}"
                                                                                class="form-control text-end price-input"
                                                                                data-item-id="{{ $item->id }}"
                                                                                step="1" min="0" />
                                                                        </td>
                                                                        <td>
                                                                            <div class="input-group">
                                                                                <input type="number"
                                                                                    name="items[{{ $item->id }}][discount]"
                                                                                    value="{{ (float) $item->discount }}"
                                                                                    class="form-control text-end discount-input"
                                                                                    style="min-width: 80px;"
                                                                                    step="1" min="0"
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
                                                                        <td class="text-end">
                                                                            <input type="text"
                                                                                name="items[{{ $item->id }}][amount]"
                                                                                value="{{ intval($item->quantity * $item->price * (1 - $item->discount / 100)) }}"
                                                                                class="form-control text-end amount-input"
                                                                                data-item-id="{{ $item->id }}"
                                                                                readonly />
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="5" class="text-end"><strong>Sub
                                                                            Total:</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="subtotal">0</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="4" class="text-end">
                                                                        <strong>Order Discount:</strong>
                                                                    </td>
                                                                    <td>
                                                                        <div class="input-group">
                                                                            <input type="number" name="order_discount"
                                                                                value="{{ (float) ($sales->order_discount ?? 0) }}"
                                                                                class="form-control text-end"
                                                                                id="discountTotalValue" step="1"
                                                                                min="0" style="min-width: 80px;" />

                                                                            <select name="order_discount_type_type"
                                                                                class="form-select" id="discountTotalType"
                                                                                style="min-width: 70px;">
                                                                                <option value="percentage"
                                                                                    {{ ($sales->order_discount_type ?? '') === 'percentage' ? 'selected' : '' }}>
                                                                                    %</option>
                                                                                <option value="fixed"
                                                                                    {{ ($sales->order_discount_type ?? '') === 'fixed' ? 'selected' : '' }}>
                                                                                    Rp</option>
                                                                            </select>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="orderDiscountTotal">0</span>
                                                                    </td>
                                                                </tr>
                                                                @if (($sales->tax_rate ?? 0) > 0)
                                                                    <tr>
                                                                        <td colspan="5" class="text-end">
                                                                            <strong>Tax ({{ $sales->tax_rate }}%):</strong>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <span id="totalTax">0</span>
                                                                            <input type="hidden" name="total_tax"
                                                                                id="total_tax_input" value="0">
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td colspan="5" class="text-end">
                                                                        <strong>Grand Total:</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="finalTotal">0</span>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                        <br>
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
    </div>
@endsection
