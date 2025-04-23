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
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}"
                                    id="invoiceForm">
                                    @csrf
                                    <input type="hidden" name="products" id="productsField">
                                    <input type="hidden" id="taxRateInput" name="tax_rate" value="{{ $tax->rate ?? 0 }}">

                                    <div class="card mb-4 border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <label class="form-label text-muted">Transaction Date</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="ti ti-calendar"></i>
                                                            </span>
                                                            <input type="date" class="form-control"
                                                                name="transaction_date" value="{{ date('Y-m-d') }}" />
                                                        </div>
                                                    </div>
                                                    <div class="me-3">
                                                        <label class="form-label text-muted">Customer</label>
                                                        <select class="form-control" name="customer_id" id="customer_id">
                                                            <option value="">Select Customer</option>
                                                            @foreach ($customers as $customer)
                                                                <option value="{{ $customer->id }}"
                                                                    data-payment-terms="{{ $customer->payment_terms }}">
                                                                    {{ $customer->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <button type="button" id="clearCart" class="btn btn-outline-danger">
                                                    <i class="ti ti-trash me-1"></i> Clear Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Product Catalog Section -->
                                        <div class="col-md-6">
                                            <div class="card card-product-catalog shadow-sm mb-4">
                                                <div
                                                    class="card-header bg-light d-flex justify-content-between align-items-center">
                                                    <h4 class="card-title mb-0">
                                                        <i class="ti ti-box me-2"></i>Product Catalog
                                                    </h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="input-group mb-4">
                                                        <span class="input-group-text">
                                                            <i class="ti ti-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control" id="searchProduct"
                                                            placeholder="Search products">
                                                    </div>
                                                    <div class="row g-2" id="productGrid">
                                                        @foreach ($products as $product)
                                                            <div class="col-md-4 mb-2">
                                                                <div
                                                                    class="card product-card h-100 border shadow-sm hover-shadow">
                                                                    <div
                                                                        class="card-img-top position-relative product-image-container">
                                                                        <img src="{{ asset($product->image) }}"
                                                                            class="img-fluid product-image"
                                                                            alt="{{ $product->name }}"
                                                                            data-product-id="{{ $product->id }}"
                                                                            data-product-name="{{ $product->name }}"
                                                                            data-product-price="{{ $product->selling_price }}"
                                                                            data-product-unit="{{ $product->unit->symbol }}">
                                                                    </div>
                                                                    <div class="card-body p-2 text-center">
                                                                        <h5 class="card-title fs-4 mb-1">
                                                                            {{ $product->name }}</h5>
                                                                        <p class="card-text fs-4">
                                                                            {{ \App\Helpers\CurrencyHelper::format($product->selling_price) }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Shopping Cart Section -->
                                        <div class="col-md-6">
                                            <div class="card shadow-sm mb-4">
                                                <div
                                                    class="card-header bg-light d-flex justify-content-between align-items-center">
                                                    <h4 class="card-title mb-0">
                                                        <i class="ti ti-shopping-cart me-2"></i>Shopping Cart
                                                    </h4>
                                                    <span id="cartCount">0</span>
                                                </div>
                                                <div class="card-body">
                                                    <div id="productList" class="list-group mb-4">
                                                        <!-- Products will be populated here -->
                                                    </div>
                                                </div>
                                                <div class="card-footer bg-light p-3">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Subtotal:</span>
                                                        <span id="subtotal" class="fw-bold">Rp 0</span>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Order Discount:</span>
                                                        <div class="input-group" style="max-width: 180px;">
                                                            <input type="number" id="orderDiscount" class="form-control"
                                                                value="0" min="0">
                                                            <select id="discountType" class="form-select">
                                                                <option value="fixed">Rp</option>
                                                                <option value="percent">%</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Tax Rate:</span>
                                                        <div class="input-group" style="max-width: 120px;">
                                                            <input type="number" id="taxRate" class="form-control"
                                                                value="0" min="0">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>

                                                    <hr>

                                                    <div class="d-flex justify-content-between fs-4 fw-bold text-primary">
                                                        <span>Grand Total:</span>
                                                        <span id="finalTotal">Rp 0</span>
                                                    </div>

                                                    <!-- Hidden Inputs -->
                                                    <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                        value="0">
                                                    <input type="hidden" id="orderDiscountInput" name="discount_total"
                                                        value="0">
                                                    <input type="hidden" id="orderDiscountTypeInput"
                                                        name="discount_total_type" value="fixed">
                                                    <input type="hidden" id="taxInput" name="tax_amount"
                                                        value="0">
                                                    <input type="hidden" id="grandTotalInput" name="grand_total"
                                                        value="0">

                                                    <!-- Submit -->
                                                    <div class="mt-3">
                                                        <button type="button" id="processPaymentBtn"
                                                            class="btn btn-primary w-100 btn-lg">
                                                            <i class="ti ti-cash me-1"></i> Process Payment
                                                        </button>
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
    @include('admin.layouts.posmodals')
@endsection
