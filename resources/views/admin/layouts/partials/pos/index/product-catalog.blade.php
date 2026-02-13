<div class="col-md-6">
    <div class="card border-0 shadow-sm rounded-3 card-product-catalog mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="ti ti-box me-2"></i>{{ __('messages.product_catalog') }}
            </h4>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-4">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchProduct" placeholder="{{ __('messages.search_products') }}">
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ti ti-barcode"></i>
                        </span>
                        <input type="text" class="form-control" id="barcodeScannerInput" placeholder="{{ __('messages.scan_barcode') }}">
                    </div>
                </div>
            </div>
            <div class="row g-2" id="productGrid">
                @if ($products->isEmpty())
                    <div class="empty">
                        <div class="empty-img">
                            <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                        </div>
                        <p class="empty-title">{{ __('messages.no_products_found') }}</p>
                        <p class="empty-subtitle text-muted">
                            {{ __('messages.it_looks_like_you_havent_added_any_products_yet') }}
                        </p>
                    </div>
                @else
                    @foreach ($products as $product)
                        <div class="col-md-4 mb-2">
                            <div class="card product-card border-0 shadow-sm rounded-3 hover-shadow" style="cursor: pointer;"
                                data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}"
                                data-product-price="{{ $product->selling_price }}"
                                data-product-unit="{{ $product->unit->symbol }}"
                                data-product-stock="{{ $product->stock_quantity }}">
                                <div class="card-img-top position-relative product-image-container d-flex align-items-center justify-content-center"
                                    style="height: 150px;">
                                    @if ($product->image == asset('img/default_placeholder.png'))
                                        <i class="ti ti-photo fs-1"
                                            style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
                                    @else
                                        <img src="{{ $product->image }}" class="img-fluid" alt="{{ $product->name }}"
                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px 4px 0 0;">
                                    @endif
                                </div>
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4 mb-1" style="max-height: 4em; overflow: hidden;">
                                        {{ $product->name }}</h5>
                                    <p class="card-text fs-4 mb-1">
                                        {{ \App\Helpers\CurrencyHelper::formatWithPosition($product->selling_price) }}
                                    </p>
                                    <p class="card-text fs-5 text-muted">
                                        {{ __('messages.in_stock') }} <span
                                            class="product-stock-display badge text-light {{ $product->stock_quantity > 10 ? 'bg-success' : ($product->stock_quantity > 0 ? 'bg-warning' : 'bg-danger') }}">{{ $product->stock_quantity }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
