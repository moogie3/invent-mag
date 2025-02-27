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
                                    <form enctype="multipart/form-data" method="POST"
                                        action="{{ route('admin.sales.store') }}" id="invoiceForm">
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
                                                    <input type="date" class="form-control" name="order_date"
                                                        id="order_date" placeholder="Order date" required />
                                                </div>
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">CUSTOMER</label>
                                                    <select class="form-control" name="customer_id" id="customer_id"
                                                        required>
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
                                                    <input type="text" class="form-control" name="quantity"
                                                        id="quantity" placeholder="Quantity" />
                                                </div>
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">CUSTOMER SELLING PRICE</label>
                                                    <input type="text" class="form-control" name="new_price"
                                                        id="new_price" placeholder="New price" />
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
                                                    <input type="text" class="form-control" name="past_price"
                                                        id="past_price" placeholder="Past price" disabled />
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
                                                    <th><button class="table-sort fs-4 py-3"
                                                            data-sort="sort-product">Product
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
        <script>
            //automatically input the product
            document.addEventListener('DOMContentLoaded', function() {
                const productSelect = document.getElementById('product_id');
                const customerSelect = document.getElementById('customer_id');
                const priceField = document.getElementById('price');
                const quantityField = document.getElementById('quantity');
                const sellPriceField = document.getElementById('selling_price');
                const newPriceField = document.getElementById('new_price');
                const pastPriceField = document.getElementById('past_price');
                const addProductButton = document.getElementById('addProduct');
                const productTableBody = document.getElementById('productTableBody');
                const productsField = document.getElementById('productsField');
                const totalPriceElement = document.getElementById('totalPrice');

                let products = []; // Array to store added products

                // Auto-fill price on product selection and fetch past price
                productSelect.addEventListener('change', function() {
                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    const price = selectedOption.getAttribute('data-price');
                    const sellprice = selectedOption.getAttribute('data-selling-price');
                    const pastprice = selectedOption.getAttribute('data-past-price');
                    const customerId = customerSelect.value;
                    const productId = productSelect.value;

                    priceField.value = price ? price : '';
                    sellPriceField.value = sellprice ? sellprice : '';

                    if (customerId && productId) {
                        fetchPastCustomerPrice(customerId, productId);
                    }
                });

                // When the customer changes
                customerSelect.addEventListener('change', function() {
                    const customerId = customerSelect.value;
                    const productId = productSelect.value;

                    // Only fetch past price if a product is already selected
                    if (customerId && productId) {
                        fetchPastCustomerPrice(customerId, productId);
                    }
                });

                // Function to fetch past customer price from the database
                function fetchPastCustomerPrice(customerId, productId) {
                    fetch(`/admin/sales/get-past-price?customer_id=${customerId}&product_id=${productId}`)
                        .then(response => response.json())
                        .then(data => {
                            console.log("Past Price Data:", data); // Debugging: Check the response in console

                            if (data && data.past_price !== null) {
                                pastPriceField.value = data.past_price; // Correctly update the input field
                            } else {
                                pastPriceField.value = "0"; // Set default value if no past price found
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching past price:', error);
                            pastPriceField.value = "0"; // Handle errors gracefully
                        });
                }


                // Add product to the table
                addProductButton.addEventListener('click', function() {
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
                    let existingProduct = products.find(p => p.id == productId);
                    if (existingProduct) {
                        existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(quantity);
                        existingProduct.total = parseFloat(existingProduct.price) * existingProduct.quantity;
                        updateTotalPrice();
                        updateHiddenField();
                        return;
                    }

                    // Add product to the list
                    const productData = {
                        id: productId,
                        name: productName,
                        quantity: quantity,
                        price: price,
                        customer_price: newPriceField.value,
                        total: total
                    };
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
                    sellPriceField.value = '';
                    newPriceField.value = '';

                    // Remove product event
                    row.querySelector('.removeProduct').addEventListener('click', function() {
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

                function formatCurrency(amount) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(amount);
                }
            });
        </script>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endsection
