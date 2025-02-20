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
                                            <div class="col-md-1 mb-3">
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
                                            <div class="col-md-4 mb-3 text-end">
                                                <label class="form-label">BUTTON</label>
                                                <button type="button" id="addProduct" class="btn btn-secondary">Add Product</button>
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                            <input type="hidden" name="products" id="productsField">
                                        </div>
                                    </fieldset>
                                    <table class="table card-table table-vcenter table-responsive">
                                        <thead style="font-size: large">
                                            <tr>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-product">Product</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-quantity">Quantity</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-price">Price</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-total">Amount</th>
                                                <th style="width:200px;text-align:center" class="fs-4 py-3">Action</th>
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
            const newPriceField = document.getElementById('new_price');
            const addProductButton = document.getElementById('addProduct');
            const productTableBody = document.getElementById('productTableBody');
            const productsField = document.getElementById('productsField');
            const totalPriceElement = document.getElementById('totalPrice');

            let products = []; // Array to store added products

            // Auto-fill price on product selection
            productSelect.addEventListener('change', function () {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                priceField.value = price ? price : '';
            });

            // Add product to the table
            addProductButton.addEventListener('click', function () {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const productId = productSelect.value;
                const productName = selectedOption.text;
                const quantity = quantityField.value;
                const price = newPriceField.value;
                const total = (parseFloat(price) * parseInt(quantity)) || 0;

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
                    <td>${formatCurrency(price)}</td>
                    <td>${formatCurrency(total)}</td>
                    <td style="text-align:center"><button type="button" class="btn btn-danger btn-sm removeProduct">Remove</button></td>
                `;
                productTableBody.appendChild(row);

                updateTotalPrice();

                // Reset fields
                productSelect.value = '';
                quantityField.value = '';
                priceField.value = '';
                newPriceField.value = '';

                // Remove product event
                row.querySelector('.removeProduct').addEventListener('click', function () {
                    row.remove();
                    products = products.filter(p => p.id !== productId);
                    updateHiddenField();
                    updateTotalPrice();
                });
            });

            // Update hidden input field with JSON data
            function updateHiddenField() {
                productsField.value = JSON.stringify(products);
            }

            // Calculate and update the total price
            function updateTotalPrice() {
                const total = products.reduce((sum, product) => sum + product.total, 0);
                totalPriceElement.innerHTML = formatCurrency(total) || 0;
            }

            // You can either call your server-side method via AJAX or handle it entirely in JS
            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
            }

        });
    </script>
    <script>
        $(document).ready(function () {
            $('#pctable').DataTable();
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
