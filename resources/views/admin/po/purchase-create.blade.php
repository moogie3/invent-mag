@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Create Purchase Order
                        </h2>
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
                                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}"
                                    id="invoiceForm">
                                    @csrf
                                    <fieldset class="form-fieldset container-xl">
                                        <div class="row">
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">INVOICE</label>
                                                <input type="text" class="form-control" name="invoice" id="invoice"
                                                    placeholder="Invoice" />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">SUPPLIER</label>
                                                <select class="form-control" name="supplier_id" id="supplier_id">
                                                    <option value="">Select Supplier</option>
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}"
                                                            data-payment-terms="{{ $supplier->payment_terms }}">
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">ORDER DATE</label>
                                                <input type="date" class="form-control" name="order_date" id="order_date"
                                                    placeholder="Order date" />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">DUE DATE</label>
                                                <input type="date" class="form-control" name="due_date" id="due_date"
                                                    placeholder="Due date" readonly />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PRODUCT</label>
                                                <select class="form-control" name="product_id" id="product_id">
                                                    <option value="">Select Product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-price="{{ $product->price }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">QTY</label>
                                                <input type="text" class="form-control" name="quantity" id="quantity"
                                                    placeholder="Quantity" />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">NEW PRICE</label>
                                                <input type="text" class="form-control" name="new_price" id="new_price"
                                                    placeholder="New price" />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">DISCOUNT</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="discount"
                                                        placeholder="Discount" />
                                                    <select class="form-select" id="discount_type">
                                                        <option value="fixed">Rp</option>
                                                        <option value="percentage">%</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">SUPPLIER PRICE</label>
                                                <input type="text" class="form-control" name="last_price" id="last_price"
                                                    placeholder="Supplier price" disabled />
                                            </div>
                                            <div class="col-md-10 mb-3 text-end">
                                                <label class="form-label">BUTTON</label>
                                                <button type="button" id="addProduct" class="btn btn-secondary">Add
                                                    Product</button>
                                                <button type="button" id="clearProducts"
                                                    class="btn btn-danger">Clear</button>
                                                <button type="submit" class="btn btn-success">Submit</button>
                                            </div>
                                            <input type="hidden" name="products" id="productsField">
                                        </div>
                                    </fieldset>
                                    <table class="table card-table table-vcenter table-responsive">
                                        <thead style="font-size: large">
                                            <tr>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-product">Product
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-quantity">Quantity
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-price">Price</th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-discount">Discount
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-total">Amount
                                                </th>
                                                <th style="width:100px;text-align:center" class="fs-4 py-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productTableBody">
                                        </tbody>
                                    </table>
                                    <div class="row mt-4 align-items-start">
                                        <div class="col-md-6 text-center">
                                            <label class="form-label">Order Discount</label>
                                            <div class="d-flex justify-content-center my-3">
                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                    <input type="number" class="form-control w-auto"
                                                        id="discountTotalValue" name="discount_total" value="0"
                                                        placeholder="Order Discount" style="max-width: 120px;">
                                                    <select class="form-select w-auto" id="discountTotalType"
                                                        name="discount_total_type" style="max-width: 90px;">
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
                                                <div class="d-flex justify-content-between fs-4 fw-bold text-primary">
                                                    <strong>Grand Total:</strong>
                                                    <span id="finalTotal">Rp 0</span>
                                                </div>
                                            </div>

                                            {{-- Hidden fields --}}
                                            <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                value="0">
                                            <input type="hidden" id="orderDiscountInput" name="discount_total"
                                                value="0">
                                            <input type="hidden" id="orderDiscountTypeInput" name="discount_total_type"
                                                value="fixed">
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
