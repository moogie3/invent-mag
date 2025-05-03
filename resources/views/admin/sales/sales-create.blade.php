@extends('admin.layouts.base')

@section('title', 'Sales')

@section('content')
    <div class="page-wrapper">
        <div class="page-header bg-light">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle text-muted">Sales</div>
                        <h2 class="page-title">Create Sales</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.sales.store') }}"
                    id="invoiceForm">
                    @csrf
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title"><i class="ti ti-report-money"></i> Sales Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Invoice</label>
                                    <input type="text" class="form-control" name="invoice" id="invoice"
                                        placeholder="Invoice Number" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Customer</label>
                                    <select class="form-select" name="customer_id" id="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                data-payment-terms="{{ $customer->payment_terms }}">
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Order Date</label>
                                    <input type="date" class="form-control" name="order_date" id="order_date" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Due Date</label>
                                    <input type="date" class="form-control bg-light" name="due_date" id="due_date"
                                        placeholder="AUTOFILL" readonly />
                                </div>
                            </div>

                            <h5 class="text-muted fw-bold mt-2 mb-3">Add Product to Order</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Product</label>
                                    <select class="form-select" name="product_id" id="product_id">
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                                data-selling-price="{{ $product->selling_price }}">
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">Quantity</label>
                                    <input type="text" class="form-control" name="quantity" id="quantity"
                                        placeholder="0" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Customer Price</label>
                                    <input type="text" class="form-control bg-light" name="past_price" id="past_price"
                                        placeholder="AUTOFILL" readonly disabled />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">New Price</label>
                                    <input type="text" class="form-control" name="customer_price" id="customer_price"
                                        placeholder="0" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Discount</label>
                                    <div class="input-group">
                                        <input type="number" min="0" class="form-control" id="discount"
                                            placeholder="0" />
                                        <select class="form-select" id="discount_type" style="max-width: 70px;">
                                            <option value="fixed">Rp</option>
                                            <option value="percentage">%</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-5">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Supplier Price</label>
                                    <input type="text" class="form-control bg-light" name="price" id="price"
                                        placeholder="AUTOFILL" readonly disabled />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Retail Price</label>
                                    <input type="text" class="form-control bg-light" name="selling_price"
                                        id="selling_price" placeholder="AUTOFILL" readonly disabled />
                                </div>
                            </div>

                            <div class="mt-3 text-end">
                                <button type="button" id="addProduct" class="btn btn-primary d-none d-sm-inline-block">
                                    <i class="ti ti-plus"></i> Add Product
                                </button>
                                <button type="button" id="clearProducts"
                                    class="btn btn-outline-danger d-none d-sm-inline-block">
                                    <i class="ti ti-trash"></i> Clear All
                                </button>
                            </div>

                            <input type="hidden" name="products" id="productsField">
                            <input type="hidden" name="tax_rate" id="taxRateInput" value="{{ $tax->rate ?? 0 }}">
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title"><i class="ti ti-box"></i> Order Items</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th>NO</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productTableBody">
                                        <!-- Dynamic Items -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><i class="ti ti-percentage"></i> Order Discount</h4>
                                </div>
                                <div class="card-body">
                                    <label class="form-label fw-bold">Apply Order Discount</label>
                                    <div class="input-group mb-2">
                                        <input type="number" min="0" class="form-control"
                                            id="discountTotalValue" name="discount_total" placeholder="0" />
                                        <select class="form-select" id="discountTotalType" name="discount_total_type"
                                            style="max-width: 80px;">
                                            <option value="fixed">Rp</option>
                                            <option value="percentage">%</option>
                                        </select>
                                        <button type="button" id="applyTotalDiscount"
                                            class="btn btn-secondary d-none d-sm-inline-block">
                                            <i class="ti ti-discount-check"></i> Apply
                                        </button>
                                    </div>
                                    <small class="text-muted">Choose % for percentage or Rp for fixed amount.</small>

                                    <input type="hidden" id="totalDiscountInput" name="total_discount" value="0">
                                    <input type="hidden" id="orderDiscountInput" name="discount_total" value="0">
                                    <input type="hidden" id="orderDiscountTypeInput" name="discount_total_type"
                                        value="fixed">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><i class="ti ti-report"></i> Order Summary</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Subtotal:</span>
                                        <span id="subtotal" class="fw-bold">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Order Discount:</span>
                                        <span id="orderDiscountTotal" class="fw-bold">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Tax ({{ $tax->rate ?? 0 }}%):</span>
                                        <span id="taxTotal" class="fw-bold">Rp 0</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fs-4 fw-bold">Grand Total:</span>
                                        <span id="finalTotal" class="fs-4 fw-bold text-primary">Rp 0</span>
                                    </div>
                                    <input type="hidden" id="taxInput" name="tax_amount" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-outline-secondary d-none d-sm-inline-block"
                            onclick="history.back()">
                            <i class="ti ti-arrow-left"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success d-none d-sm-inline-block">
                            <i class="ti ti-device-floppy"></i> Save Sales Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
