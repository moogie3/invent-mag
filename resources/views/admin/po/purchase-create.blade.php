@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="page-wrapper">
    <!-- Page header -->
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
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-body">
                            <form enctype="multipart/form-data" method="POST"
                                action="{{ route('admin.po.store') }}" id="invoiceForm">
                                @csrf
                                <fieldset class="form-fieldset container-xl">
                                    <div class="row">
                                        <div class="col-md-1 mb-3">
                                            <label class="form-label">INVOICE</label>
                                            <input type="text" class="form-control" name="invoice" id="invoice"
                                                placeholder="Invoice" required />
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">SUPPLIER</label>
                                            <select class="form-control" name="supplier_id" id="supplier_id" required>
                                                <option value="">Select Supplier</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" data-payment-terms="{{ $supplier->payment_terms }}">
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">ORDER DATE</label>
                                            <input type="date" class="form-control" name="order_date" id="order_date"
                                                placeholder="Order date" required />
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">DUE DATE</label>
                                            <input type="date" class="form-control" name="due_date" id="due_date" placeholder="Due date"/>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">PAYMENT TYPE</label>
                                            <select class="form-control" name="payment_type" required>
                                                <option>Select payment type</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Transfer">Transfer</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">PAYMENT STATUS</label>
                                            <select class="form-control" name="status" required>
                                                <option>Select status</option>
                                                <option value="Paid">Paid</option>
                                                <option value="Unpaid">Unpaid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">PRODUCT</label>
                                            <select class="form-control" name="product_id" id="product_id" required>
                                                <option value="">Select Product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-quantity="{{ $product->quantity }}">
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">QTY</label>
                                            <input type="text" class="form-control" name="quantity"
                                                id="quantity" placeholder="Quantity" required />
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">PRICE</label>
                                            <input type="text" class="form-control" name="selling_price" id="selling_price" placeholder="Selling Price" />
                                        </div>
                                        <input type="hidden" name="products" id="productsField">
                                    </div>
                                    <div class="text-end">
                                        <button type="button" id="addProduct" class="btn btn-secondary">Add Product</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </fieldset>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productTableBody">
                                    </tbody>
                                </table>
                                <h4>Total: <span id="totalPrice">0</span></h4>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    //automatically input the due date
    document.addEventListener('DOMContentLoaded', function () {
    const orderDateField = document.getElementById('order_date');
    const dueDateField = document.getElementById('due_date');
    const supplierSelect = document.getElementById('supplier_id');

    // Event listener for supplier selection change
    supplierSelect.addEventListener('change', function () {
        calculateDueDate();
    });

    // Event listener for order date selection change
    orderDateField.addEventListener('change', function () {
        calculateDueDate();
    });

    // Function to calculate the due date
    function calculateDueDate() {
        const orderDate = new Date(orderDateField.value);
        const paymentTerms = supplierSelect.options[supplierSelect.selectedIndex]?.dataset.paymentTerms;

        if (orderDate && paymentTerms) {
            // Calculate the due date by adding payment terms (in days) to the order date
            orderDate.setDate(orderDate.getDate() + parseInt(paymentTerms));

            // Format the due date to YYYY-MM-DD
            const dueDate = orderDate.toISOString().split('T')[0];
            dueDateField.value = dueDate;
        }
    }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('product_id');
            const quantityField = document.getElementById('quantity');
            const priceField = document.getElementById('selling_price');

            // Listen for changes on the product select dropdown
            productSelect.addEventListener('change', function () {
                const selectedOption = productSelect.options[productSelect.selectedIndex];

                // Get the price and quantity data attributes from the selected option
                const price = selectedOption.getAttribute('data-price');
                const quantity = selectedOption.getAttribute('data-quantity');

                // Set the price and quantity fields automatically
                priceField.value = price ? price : '';  // set price if available
                quantityField.value = quantity ? quantity : '';  // set stock quantity if available
            });
        });
</script>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
