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
                        <div class="card card-primary" style="padding: 10px;">
                            <div class="card-body">
                                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}"
                                    id="invoiceForm">
                                    @csrf
                                    <fieldset class="form-fieldset">
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">INVOICE</label>
                                                <input type="text" class="form-control" name="invoice" id="invoice"
                                                    placeholder="Invoice" />
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">ORDER DATE</label>
                                                <input type="date" class="form-control" name="order_date" id="order_date"
                                                    placeholder="Order date" />
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">CUSTOMER</label>
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
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">PRODUCT</label>
                                                <select class="form-control" name="product_id" id="product_id">
                                                    <option value="">Select Product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-price="{{ $product->price }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">QTY</label>
                                                <input type="text" class="form-control" name="quantity" id="quantity"
                                                    placeholder="Quantity" />
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">PRICE</label>
                                                <input type="text" class="form-control" name="new_price" id="new_price"
                                                    placeholder="Price" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mt-2">
                                                <button type="button" id="addProduct" class="btn btn-secondary">Add
                                                    Product</button>
                                            </div>
                                            <div class="col-md-9 mt-2 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="products" id="productsField">
                                    </fieldset>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h2>Invoice</h2>
                                            <div class="card">
                                                <div class="card-body">
                                                    <div id="productList" class="list-group"></div>
                                                    <h1 class="mt-3 text-end">Total Price: <span id="totalPrice"></span>
                                                    </h1>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h2>Product List</h2>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" id="searchProduct"
                                                    placeholder="Search Product...">
                                            </div>
                                            <div class="row" id="productGrid"
                                                style="max-height: 500px; overflow-y: auto;">
                                                @foreach ($products as $product)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="card text-center">
                                                            <img src="{{ asset($product->image) }}"
                                                                class="card-img-top product-image"
                                                                alt="{{ $product->name }}"
                                                                style="height: 85px; object-fit: cover; cursor: pointer;"
                                                                data-product-id="{{ $product->id }}"
                                                                data-product-name="{{ $product->name }}"
                                                                data-product-price="{{ $product->price }}">
                                                            <div class="card-body p-1">
                                                                <h5 class="card-title" style="font-size: 12px;">
                                                                    {{ $product->name }}</h5>
                                                                <p class="card-text text-muted" style="font-size: 10px;">
                                                                    {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
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
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productList = document.getElementById('productList');
        const totalPriceElement = document.getElementById('totalPrice');
        const addProductBtn = document.getElementById('addProduct');
        const productSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const priceInput = document.getElementById('new_price');
        const productsField = document.getElementById('productsField');
        const productGrid = document.getElementById('productGrid');
        let products = JSON.parse(localStorage.getItem('cachedProducts')) || [];

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        }

        function saveProductsToCache() {
            localStorage.setItem('cachedProducts', JSON.stringify(products));
        }

        function renderList() {
            productList.innerHTML = '';
            let total = 0;

            products.forEach((product, index) => {
                const item = document.createElement('div');
                item.classList.add('list-group-item', 'd-flex', 'justify-content-between',
                    'align-items-center');
                item.innerHTML = `
                <div>
                    <strong>${product.name}</strong><br>
                    ${product.quantity} x ${formatCurrency(product.price)}
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${formatCurrency(product.total)}</strong>
                    <button class="btn btn-sm btn-danger remove-product ms-3" data-index="${index}"><i class="ti ti-trash"></i></button>
                </div>
            `;
                productList.appendChild(item);
                total += product.total;
            });

            totalPriceElement.innerText = formatCurrency(total);
            productsField.value = JSON.stringify(products); // Save for form submission
            saveProductsToCache();
        }

        function addToProductList(productId, productName, price) {
            let existingProduct = products.find(p => p.id === productId);

            if (existingProduct) {
                existingProduct.quantity += 1;
                existingProduct.total = existingProduct.quantity * existingProduct.price;
            } else {
                products.push({
                    id: productId,
                    name: productName,
                    price: parseFloat(price),
                    quantity: 1,
                    total: parseFloat(price)
                });
            }

            renderList();
        }

        addProductBtn.addEventListener('click', function() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productId = productSelect.value;
            const productName = selectedOption.text;
            const price = parseFloat(priceInput.value || 0);
            const quantity = parseInt(quantityInput.value || 1);

            if (!productId || price <= 0 || quantity <= 0) {
                alert('Please select a valid product, quantity, and price.');
                return;
            }

            let existingProduct = products.find(p => p.id === productId);
            if (existingProduct) {
                existingProduct.quantity += quantity;
                existingProduct.total = existingProduct.quantity * existingProduct.price;
            } else {
                products.push({
                    id: productId,
                    name: productName,
                    price,
                    quantity,
                    total: price * quantity
                });
            }

            renderList();
            productSelect.value = "";
            quantityInput.value = "";
            priceInput.value = "";
        });

        productGrid.addEventListener('click', function(event) {
            const target = event.target.closest('.product-image');
            if (!target) return;

            const productId = target.dataset.productId;
            const productName = target.dataset.productName;
            const productPrice = target.dataset.productPrice;

            addToProductList(productId, productName, productPrice);
        });

        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            priceInput.value = selectedOption.getAttribute('data-price');
        });

        // Event Listener to Remove Products
        productList.addEventListener('click', function(event) {
            const target = event.target.closest('.remove-product'); // Ensure we get the button
            if (!target) return; // If the clicked element is not a remove button, ignore it

            const index = target.dataset.index;
            if (index !== undefined) {
                products.splice(index, 1);
                renderList();
            }
        });

        // Load Cached Products on Page Load
        renderList();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchProduct');
        const productCards = document.querySelectorAll('#productGrid .col-md-4');

        if (!searchInput) {
            console.error('Search input not found.');
            return;
        }

        searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase()
                .trim(); // Convert to lowercase and remove spaces

            productCards.forEach(card => {
                const productNameElement = card.querySelector('.card-title');

                if (!productNameElement) {
                    console.error('Product name element not found in card:', card);
                    return;
                }

                const productName = productNameElement.textContent.toLowerCase()
                    .trim(); // Ensure text is retrieved

                // Debugging logs
                console.log(
                    `Searching for: "${searchText}", Current Product: "${productName}"`);

                // Show/hide product based on search input
                if (productName.includes(searchText)) {
                    card.style.display = ''; // Show matching products
                } else {
                    card.style.display = 'none'; // Hide non-matching products
                }
            });
        });
    });
</script>
