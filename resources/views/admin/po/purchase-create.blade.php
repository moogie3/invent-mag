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
                                                <button type="submit" class="btn btn-primary">Submit</button>
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
                                    <small class="text-muted d-block mt-1 text-end">
                                        Select <strong>%</strong> for percentage or
                                        <strong>Rp</strong> for fixed discount.
                                    </small>
                                    <div class="row mt-4">
                                        <div class="col-md-12 text-end">
                                            <h3 class="mb-2">Subtotal: <span id="subtotal">0</span></h3>
                                            <h3 class="mb-2">Discount: <span id="discountTotal">0</span></h3>
                                            <h3 class="mb-2">Grand Total: <span id="finalTotal">0</span></h3>
                                            <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                value="0">
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
