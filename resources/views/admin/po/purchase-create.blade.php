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
                                                <label class="form-label">ORDER DATE</label>
                                                <input type="date" class="form-control" name="order_date" id="order_date" placeholder="Order date" required />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">DUE DATE</label>
                                                <input type="date" class="form-control" name="due_date" id="due_date" placeholder="Due date" readonly/>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
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
                                                <label class="form-label">PRODUCT</label>
                                                <select class="form-control" name="product_id" id="product_id">
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">QTY</label>
                                                <input type="text" class="form-control" name="quantity"
                                                    id="quantity" placeholder="Quantity"/>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">LAST PRICE</label>
                                                <input type="text" class="form-control" name="last_price" id="last_price" placeholder="Last price" disabled/>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">NEW PRICE</label>
                                                <input type="text" class="form-control" name="new_price" id="new_price" placeholder="New price"/>
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
                                    <h1 class="text-end">Total Price: <span id="totalPrice">
                                        </span></h1>
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

        // event listener for supplier selection change
        supplierSelect.addEventListener('change', function () {
            calculateDueDate();
        });

        // event listener for order date selection change
        orderDateField.addEventListener('change', function () {
            calculateDueDate();
        });

        // function to calculate the due date
            function calculateDueDate() {
                const orderDateValue = orderDateField.value;
                const orderDate = new Date(orderDateValue);

                // check if the order date is valid
                if (isNaN(orderDate.getTime())) {
                    console.error('Invalid order date:', orderDateValue);
                    alert('Please select a valid order date.');
                    return;  // exit the function if the date is invalid
                }

                const paymentTerms = supplierSelect.options[supplierSelect.selectedIndex]?.dataset.paymentTerms;

                if (paymentTerms) {
                    // calculate the due date by adding payment terms (in days) to the order date
                    orderDate.setDate(orderDate.getDate() + parseInt(paymentTerms));

                    // format the due date to YYYY-MM-DD
                    const dueDate = orderDate.toISOString().split('T')[0];
                    dueDateField.value = dueDate;
                }
            }
        });
    </script>
    <script>
        //automatically input the product
        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('product_id');
            const priceField = document.getElementById('last_price');
            const quantityField = document.getElementById('quantity');
            const newpriceField = document.getElementById('new_price');
            const addProductButton = document.getElementById('addProduct');
            const productTableBody = document.getElementById('productTableBody');
            const productsField = document.getElementById('productsField');

            let products = []; // Array to store added products

            // Auto-fill price on product selection
            productSelect.addEventListener('change', function () {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                priceField.value = price ? price : '';
            });
            const quantity = selectedOption.getAttribute('quantity');
            const new_price = selectedOption.getAttribute('new_price');
            quantityField.value = quantity ? quantity : '';
            newpriceField.value = new_price ? new_price : '';
            // Add product to the table
            addProductButton.addEventListener('click', function () {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const productId = productSelect.value;
                const productName = selectedOption.text;
                const quantity = quantityField.value;
                const price = newpriceField.value;
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
