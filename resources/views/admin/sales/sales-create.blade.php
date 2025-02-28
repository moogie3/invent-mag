@extends('admin.layouts.base')

@section('title', 'Sales')

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
                            Create Sales
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
                                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.sales.store') }}"
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
                                                <label class="form-label">ORDER DATE</label>
                                                <input type="date" class="form-control" name="order_date" id="order_date"
                                                    placeholder="Order date" />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">CUSTOMER</label>
                                                <select class="form-control" name="customer_id" id="customer_id">
                                                    <option value="">Select Customer</option>
                                                    @foreach ($customers as $customer)
                                                        <option value="{{ $customer->id }}">
                                                            {{ $customer->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">PRODUCT</label>
                                                <select class="form-control" name="product_id" id="product_id">
                                                    <option value="">Select Product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-price="{{ $product->price }}"
                                                            data-selling-price="{{ $product->selling_price }}">
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
                                                <label class="form-label">CUSTOMER SELLING PRICE</label>
                                                <input type="text" class="form-control" name="new_price" id="new_price"
                                                    placeholder="New price" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PRICE FROM SUPPLIER</label>
                                                <input type="text" class="form-control" name="price" id="price"
                                                    placeholder="Price" disabled />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">SELLING PRICE</label>
                                                <input type="text" class="form-control" name="selling_price"
                                                    id="selling_price" placeholder="Selling price" disabled />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">CUSTOMER PAST PRICE</label>
                                                <input type="text" class="form-control" name="past_price" id="past_price"
                                                    placeholder="Past price" disabled />
                                            </div>
                                            <div class="col-md-6 mb-3 text-end">
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
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-price">Price
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-total">Amount
                                                </th>
                                                <th style="width:200px;text-align:center" class="fs-4 py-3">Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="productTableBody">
                                        </tbody>
                                    </table>
                                    <h1 class="mt-3 text-end">Total Price: <span id="totalPrice">
                                        </span></h1>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
