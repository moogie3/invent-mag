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
                                    <div class="d-flex align-items-center">
                                        <label for="order_date" class="me-2">ORDER DATE :</label>
                                        <input type="datetime-local" class="form-control" name="order_date" id="order_date"
                                            value="{{ date('Y-m-d\TH:i') }}" style="width: 200px;" readonly />
                                    </div>
                                    <input type="hidden" name="products" id="productsField">
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h2>Product List</h2>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" id="searchProduct"
                                                    placeholder="Search Product">
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
                                                                data-product-price="{{ $product->price }}"
                                                                data-product-unit="{{ $product->unit->symbol }}">
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
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-body" id="invoiceContainer">
                                                    <h2>Invoice</h2>
                                                    <div id="productList" class="list-group"></div>
                                                    <div id="totalPriceContainer" class="text-end">
                                                        <h1 class="mt-3">Total : <span id="totalPrice">Rp 0</span>
                                                        </h1>
                                                    </div>
                                                    <div>
                                                        <button type="submit"
                                                            class="btn btn-primary w-100">Payment</button>
                                                    </div>
                                                </div>
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
        const productGrid = document.getElementById('productGrid');
        const productsField = document.getElementById('productsField');
        let products = JSON.parse(localStorage.getItem('cachedProducts')) || [];

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
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
                ${product.quantity} x ${formatCurrency(product.price)} / ${product.unit}
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <strong>${formatCurrency(product.total)}</strong>
                <button class="btn btn-sm btn-success increase-product ms-2" data-index="${index}">
                    <i class="ti ti-plus"></i>
                </button>
                <button class="btn btn-sm btn-warning decrease-product ms-3" data-index="${index}">
                    <i class="ti ti-minus"></i>
                </button>
                <button class="btn btn-sm btn-danger remove-product ms-3" data-index="${index}">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        `;
                productList.appendChild(item);
                total += product.total;
            });

            // Always show total price, even if it's 0
            totalPriceElement.innerText = formatCurrency(total);
            productsField.value = JSON.stringify(products);
            saveProductsToCache();
        }


        function addToProductList(productId, productName, productPrice, productUnit) {
            let existingProduct = products.find(p => p.id === productId);

            if (existingProduct) {
                existingProduct.quantity += 1;
                existingProduct.total = existingProduct.quantity * existingProduct.price;
            } else {
                products.push({
                    id: productId,
                    name: productName,
                    price: parseFloat(productPrice),
                    quantity: 1,
                    total: parseFloat(productPrice),
                    unit: productUnit
                });
            }

            renderList();
        }

        productGrid.addEventListener('click', function(event) {
            const target = event.target.closest('.product-image');
            if (!target) return;

            const productId = target.dataset.productId;
            const productName = target.dataset.productName;
            const productPrice = target.dataset.productPrice;
            const productUnit = target.dataset.productUnit;

            addToProductList(productId, productName, productPrice, productUnit);
        });

        productList.addEventListener('click', function(event) {
            const target = event.target.closest('.remove-product');
            if (!target) return;

            const index = target.dataset.index;
            if (index !== undefined) {
                products.splice(index, 1);
                renderList();
            }
        });

        productList.addEventListener('click', function(event) {
            const target = event.target.closest('.decrease-product');
            if (!target) return;

            const index = target.dataset.index;
            if (index !== undefined) {
                if (products[index].quantity > 1) {
                    products[index].quantity -= 1;
                    products[index].total = products[index].quantity * products[index].price;
                } else {
                    products.splice(index, 1); // Remove product if quantity reaches 0
                }
                renderList();
            }
        });

        productList.addEventListener('click', function(event) {
            const target = event.target.closest('.increase-product');
            if (!target) return;

            const index = target.dataset.index;
            if (index !== undefined) {
                products[index].quantity += 1;
                products[index].total = products[index].quantity * products[index].price;
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
            const searchText = this.value.toLowerCase().trim();

            productCards.forEach(card => {
                const productNameElement = card.querySelector('.card-title');
                if (!productNameElement) {
                    console.error('Product name element not found in card:', card);
                    return;
                }

                const productName = productNameElement.textContent.toLowerCase().trim();
                card.style.display = productName.includes(searchText) ? '' : 'none';
            });
        });
    });
</script>
