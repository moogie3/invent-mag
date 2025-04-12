@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header ">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        @if ($pos->status == 'Unpaid')
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title ">Edit Purchase Order</h2>
                        @else
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title ">PO Invoice Information</h2>
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
                                    action="{{ route('admin.po.update', $pos->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <h1 class="text-center">Edit Invoice {{ $pos->invoice }} {{ $pos->supplier->code }}</h1>
                                    <fieldset class="form-fieldset">
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PAYMENT TYPE</label>
                                                <select class="form-control" name="payment_type" id="payment_type"
                                                    {{ $pos->status }}>
                                                    <option value="Cash"
                                                        {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>
                                                        Cash</option>
                                                    <option value="Transfer"
                                                        {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>
                                                        Transfer</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">STATUS</label>
                                                <select class="form-control" name="status" id="status"
                                                    {{ $pos->status }}>
                                                    <option value="Paid" {{ $pos->status == 'Paid' ? 'selected' : '' }}>
                                                        Paid
                                                    </option>
                                                    <option value="Unpaid" {{ $pos->status == 'Unpaid' ? 'selected' : '' }}>
                                                        Unpaid</option>
                                                </select>
                                            </div>
                                            <div class="col-md-9 mb-3 mt-4 text-end">
                                                <button type="submit" class="btn btn-success">Save</button>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <div class="page-wrapper">
                                        <div class="page-body">
                                            <div class="container-xl">
                                                <div class="card card-lg">
                                                    <div class="card-body">
                                                        <div class="col-auto">
                                                            <h3>PO / {{ $pos->invoice }} / {{ $pos->supplier->location }} /
                                                                {{ $pos->supplier->code }}</h3>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <p class="h1">{{ $pos->supplier->name }}</p>
                                                                <address>
                                                                    {{ $pos->supplier->address }}<br>
                                                                    {{ $pos->supplier->phone_number }}<br>
                                                                    Order Date :
                                                                    {{ $pos->order_date->format('d-m-Y') }}
                                                                </address>
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <p class="h3">Payment Status</p>
                                                                <h3>
                                                                    @if ($pos->status !== 'Paid')
                                                                        <span class="badge bg-blue-lt">
                                                                            Payment Pending
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-green-lt">
                                                                            Paid in
                                                                            {{ $pos->payment_date->setTimezone(auth()->user()->timezone)->format('d F Y') }}<br>
                                                                            {{ $pos->payment_date->setTimezone(auth()->user()->timezone)->format('H:i:s') }}
                                                                        </span>
                                                                    @endif
                                                                </h3>
                                                                <address>
                                                                    Due Date : {{ $pos->due_date->format('d-m-Y') }}
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
                                                                    <th class="text-end" style="width: 13%">Discount</th>
                                                                    <th class="text-end" style="width: 20%">Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($pos->items as $index => $item)
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
                                                                                value="{{ intval($item->price) }}"
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
                                                                                    style="min-width: 80px;" step="0.01"
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
                                                                        <td class="text-end">
                                                                            <input type="text"
                                                                                name="items[{{ $item->id }}][amount]"
                                                                                value="{{ intval($item->quantity * $item->price - $item->discount) }}"
                                                                                class="form-control text-end amount-input"
                                                                                data-item-id="{{ $item->id }}"
                                                                                readonly />
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="5" class="text-end">
                                                                        <strong>Sub Total :</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="subtotal">
                                                                            0
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="5" class="text-end">
                                                                        <strong>Discount :</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="discountTotal">
                                                                            0
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="5" class="text-end">
                                                                        <strong>Grand Total :</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="finalTotal">
                                                                            0
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                <input type="hidden" id="totalDiscountInput"
                                                                    name="total_discount" value="0">
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
