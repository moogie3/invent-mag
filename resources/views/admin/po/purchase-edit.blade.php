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
                        Edit Purchase Order
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
                            <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}"
                                id="invoiceForm">
                                @csrf
                                <fieldset class="form-fieldset container-xl">
                                    <div class="row">
                                        <div class="col-md-1 mb-3">
                                            <label class="form-label">INVOICE</label>
                                            <input type="text" class="form-control" name="invoice" id="invoice"
                                                placeholder="Invoice" value="{{$pos->invoice}}"  disabled/>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">ORDER DATE</label>
                                            <input type="date" class="form-control" name="order_date" id="order_date"
                                                placeholder="Order date" value="{{$pos->order_date}}" disabled/>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">DUE DATE</label>
                                            <input type="date" class="form-control" name="due_date" id="due_date"
                                                placeholder="Due date" value="{{$pos->due_date}}" disabled/>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SUPPLIER</label>
                                            <input type="text" class="form-control" name="supplier" id="supplier" placeholder="Order date"
                                                value="{{$pos->supplier->name}}" disabled />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">PAYMENT TYPE</label>
                                            <select class="form-control" name="payment_type" id="payment_type">
                                                <option value="Cash" {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="Transfer" {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">STATUS</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="Cash" {{ $pos->status == 'Paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="Unpaid" {{ $pos->status == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                            </select>
                                        </div>
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
                                        @foreach ($items as $index => $po)
                                            <tr>
                                                <td style="display: none;">{{ $po->id }}</td>
                                                <td>{{$index + 1}}</td>
                                                <td>{{ $po->name }}</td>
                                                <td>{{ $po->quantity }}</td>
                                                <td>{{ $po->price }}</td>
                                                <td>{{ $po->total }}</td>
                                            </tr>
                                        @endforeach
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
            const orderDateValue = orderDateField.value;
            const orderDate = new Date(orderDateValue);

            // Check if the order date is valid
            if (isNaN(orderDate.getTime())) {
                console.error('Invalid order date:', orderDateValue);
                alert('Please select a valid order date.');
                return;  // Exit the function if the date is invalid
            }

            const paymentTerms = supplierSelect.options[supplierSelect.selectedIndex]?.dataset.paymentTerms;

            if (paymentTerms) {
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
        const addProductButton = document.getElementById('addProduct');
        const productTableBody = document.getElementById('productTableBody');
        const productsField = document.getElementById('productsField');

        let products = []; // Array to store added products

        // Auto-fill quantity and price on product selection
        productSelect.addEventListener('change', function () {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const quantity = selectedOption.getAttribute('data-quantity');

            priceField.value = price ? price : '';
            quantityField.value = quantity ? quantity : '';
        });

        // Add product to the table
        addProductButton.addEventListener('click', function () {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productId = productSelect.value;
            const productName = selectedOption.text;
            const quantity = quantityField.value;
            const price = priceField.value;
            const total = (parseFloat(price) * parseInt(quantity)).toFixed(2);

            if (!productId || !quantity || !price) {
                alert('Please select a product and enter quantity and price.');
                return;
            }

            // Prevent duplicate products
            if (products.some(p => p.id == productId)) {
                alert('Product already added.');
                return;
            }

            // Add product to the list
            const productData = { id: productId, name: productName, quantity: quantity, price: price, total: total };
            products.push(productData);
            updateHiddenField();

            // Append new row to the table
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${productName}</td>
                <td>${quantity}</td>
                <td>${price}</td>
                <td>${total}</td>
                <td><button type="button" class="btn btn-danger btn-sm removeProduct">Remove</button></td>
            `;
            productTableBody.appendChild(row);

            // Reset fields
            productSelect.value = '';
            quantityField.value = '';
            priceField.value = '';

            // Remove product event
            row.querySelector('.removeProduct').addEventListener('click', function () {
                row.remove();
                products = products.filter(p => p.id !== productId);
                updateHiddenField();
            });
        });

        // Update hidden input field with JSON data
        function updateHiddenField() {
            productsField.value = JSON.stringify(products);
        }

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
