@extends('admin.layouts.base')

@section('title', 'POS')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">Point of Sales (POS)</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary" style="padding: 10px;">
                            <div class="card-body">
                                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}"
                                    id="invoiceForm">
                                    @csrf
                                    <input type="hidden" name="products" id="productsField">

                                    <div class="row mb-4">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">CUSTOMER</label>
                                            <select class="form-control" name="customer_id">
                                                <option value="">Select Customer</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}">
                                                        {{ $customer->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">TRANSACTION DATE</label>
                                            <input type="date" class="form-control" name="transaction_date"
                                                value="{{ date('Y-m-d') }}" />
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h4 class="mb-3">Product List</h4>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" id="searchProduct"
                                                    placeholder="Search Product">
                                            </div>
                                            <div class="row" id="productGrid"
                                                style="max-height: 500px; overflow-y: auto;">
                                                @foreach ($products as $product)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="card text-center product-card">
                                                            <img src="{{ asset($product->image) }}"
                                                                class="card-img-top product-image"
                                                                alt="{{ $product->name }}"
                                                                style="height: 85px; object-fit: cover; cursor: pointer;"
                                                                data-product-id="{{ $product->id }}"
                                                                data-product-name="{{ $product->name }}"
                                                                data-product-price="{{ $product->selling_price }}"
                                                                data-product-unit="{{ $product->unit->symbol }}">
                                                            <div class="card-body p-1">
                                                                <h5 class="card-title" style="font-size: 12px;">
                                                                    {{ $product->name }}</h5>
                                                                <p class="card-text text-muted" style="font-size: 10px;">
                                                                    {{ \App\Helpers\CurrencyHelper::format($product->selling_price) }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h4 class="card-title">Order Summary</h4>
                                                </div>
                                                <div class="card-body" id="invoiceContainer">
                                                    <div id="productList" class="list-group mb-4"></div>

                                                    <div class="row mt-4 align-items-start">
                                                        <div class="col-md-6 text-center">
                                                            <label class="form-label">Order Discount</label>
                                                            <div class="d-flex justify-content-center my-3">
                                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                                    <input type="number" class="form-control w-auto"
                                                                        id="discountTotalValue" name="discount_total"
                                                                        value="0" placeholder="Order Discount"
                                                                        style="max-width: 100px;">
                                                                    <select class="form-select w-auto"
                                                                        id="discountTotalType" name="discount_total_type"
                                                                        style="max-width: 90px;">
                                                                        <option value="fixed">Rp</option>
                                                                        <option value="percentage">%</option>
                                                                    </select>
                                                                    <button class="btn btn-secondary" type="button"
                                                                        id="applyTotalDiscount">Apply</button>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted d-block mt-1 text-center">
                                                                Select <strong>%</strong> for percentage or
                                                                <strong>Rp</strong> for fixed discount.
                                                            </small>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="card p-3">
                                                                <div class="d-flex justify-content-between">
                                                                    <strong>Subtotal:</strong>
                                                                    <span id="subtotal">Rp 0</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <strong>Order Discount:</strong>
                                                                    <span id="orderDiscountTotal">Rp 0</span>
                                                                </div>
                                                                <hr class="my-2" />
                                                                <div
                                                                    class="d-flex justify-content-between fs-4 fw-bold text-primary">
                                                                    <strong>Grand Total:</strong>
                                                                    <span id="finalTotal">Rp 0</span>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" id="totalDiscountInput"
                                                                name="total_discount" value="0">
                                                            <input type="hidden" id="orderDiscountInput"
                                                                name="discount_total" value="0">
                                                            <input type="hidden" id="orderDiscountTypeInput"
                                                                name="discount_total_type" value="fixed">
                                                        </div>
                                                    </div>

                                                    <div class="mt-4">
                                                        <button type="submit"
                                                            class="btn btn-primary w-100 btn-lg">Process Payment</button>
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
