<!-- Delete Product Modal -->
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this Product?
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Product Details Modal -->
<div class="modal modal-blur fade" id="viewProductModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="ti ti-box me-2"></i>Product Details</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="viewProductModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading product details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto">
                    <small><i class="ti ti-info-circle me-1"></i> View complete product details</small>
                </div>
                <button type="button" class="btn btn-secondary" id="productModalPrint">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                <a href="#" class="btn btn-primary" id="productModalEdit">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Products Modal -->
<div class="modal modal-blur fade" id="lowStockModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h4 class="modal-title"><i class="ti ti-alert-triangle me-2"></i>Low Stock Products</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-center">Threshold</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lowStockProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    @php
                                        [
                                            $stockBadgeClass,
                                            $stockBadgeText,
                                        ] = \App\Helpers\ProductHelper::getStockClassAndText($product);
                                    @endphp
                                    <td class="text-center">
                                        <span class="{{ $stockBadgeClass }}">
                                            {{ $product->stock_quantity }}
                                            @if ($stockBadgeText)
                                                <small>({{ $stockBadgeText }})</small>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $product->low_stock_threshold ?? 10 }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.product.edit', $product->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit me-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="text-muted small mt-3">
                        <i class="ti ti-info-circle me-1"></i> Default low stock threshold is 10 if not specified.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-lt" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Expiring Soon Products Modal -->
<div class="modal modal-blur fade" id="expiringSoonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h4 class="modal-title"><i class="ti ti-calendar-time me-2"></i>Expiring Soon Products</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-center">Expiry Date</th>
                                <th class="text-center">Stock</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expiringSoonProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td class="text-center">
                                        @php
                                            [
                                                $badgeClass,
                                                $badgeText,
                                            ] = \App\Helpers\ProductHelper::getExpiryClassAndText(
                                                $product->expiry_date,
                                            );
                                        @endphp
                                        <span class="{{ $badgeClass }}">
                                            {{ $product->expiry_date->format('d F Y') }}
                                            @if ($badgeText)
                                                <small>({{ $badgeText }})</small>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $product->stock_quantity }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.product.edit', $product->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit me-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-lt" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Product Details View Modal Content -->
<div id="productModalViewTemplate" style="display: none;">
    <div class="card shadow">
        <!-- Header Section -->
        <div class="card-header py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0 fw-bold" id="productName"></h2>
                    <div class="text-muted" id="productCode"></div>
                    <span class="badge fs-5" id="stockStatus"></span>
                </div>
            </div>
        </div>

        <!-- Body Section -->
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Product Image -->
                <div class="col-md-4">
                    <div class="text-center mb-3">
                        <img id="productImage" src="" alt="Product Image"
                            class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain;">
                    </div>
                </div>

                <!-- Product Details -->
                <div class="col-md-8">
                    <div class="row g-3">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="ti ti-info-circle me-2 text-primary"></i>Basic Information
                                    </h5>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Category:</span>
                                        <span id="productCategory"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Unit:</span>
                                        <span id="productUnit"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Stock Quantity:</span>
                                        <span id="productQuantity"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Low Stock Threshold:</span>
                                        <span>
                                            <span id="productThreshold"></span>
                                            <small class="text-muted" id="thresholdDefaultNote"></small>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier & Storage -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="ti ti-building-store me-2 text-primary"></i>Supplier & Storage
                                    </h5>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Supplier:</span>
                                        <span id="productSupplier"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Warehouse:</span>
                                        <span id="productWarehouse"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Expiry Date:</span>
                                        <span id="productExpiry"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Information -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="ti ti-currency me-2 text-primary"></i>Pricing Information
                                    </h5>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Purchase Price:</span>
                                        <span id="productPrice"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Selling Price:</span>
                                        <span id="productSellingPrice"></span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="fw-semibold">Profit Margin:</span>
                                        <span id="productMargin"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="ti ti-notes me-2 text-primary"></i>Additional Information
                                    </h5>
                                    <div id="productDescriptionContainer">
                                        <span class="fw-semibold">Description:</span>
                                        <p id="productDescription" class="text-muted mb-0 mt-2"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
