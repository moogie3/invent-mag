<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>Are you sure?</h3>
                <div class="text-muted">Do you really want to remove this product? This action cannot be undone.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                        <div class="col">
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Products Modal -->
<div class="modal modal-blur fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="bulkDeleteModalLabel">
                    <i class="ti ti-trash me-2"></i>
                    Confirm Bulk Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-warning d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-alert-circle me-2 fs-4"></i>
                        <div>
                            <strong>Warning!</strong> This action cannot be undone.
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    You are about to permanently delete
                    <strong id="bulkDeleteCount">0</strong>
                    product(s) and all associated data.
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> What will be deleted:</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-danger me-1"></i> Product records</li>
                        <li><i class="ti ti-check text-danger me-1"></i> Product images and attachments</li>
                        <li><i class="ti ti-check text-danger me-1"></i> Stock quantity data</li>
                        <li><i class="ti ti-check text-danger me-1"></i> Related purchase history</li>
                        <li><i class="ti ti-check text-danger me-1"></i> Associated sales records</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="ti ti-info-circle me-2 fs-4 mt-1"></i>
                        <div>
                            <strong>Impact on System:</strong>
                            <ul class="mb-0 mt-1 small">
                                <li><strong>Inventory:</strong> Stock levels will be permanently removed</li>
                                <li><strong>Reports:</strong> Historical data may be affected</li>
                                <li><strong>Categories:</strong> Empty categories will remain unchanged</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                    <i class="ti ti-trash me-1"></i>
                    Delete Selected Products
                </button>
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
                                <th>Product Name</th>
                                <th class="text-center">PO ID</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Expiry Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="expiringSoonProductsTableBody">
                            <!-- Content will be loaded by JavaScript -->
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

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-fill" id="productDetailsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info-pane" type="button" role="tab" aria-controls="basic-info-pane" aria-selected="true">
                    Basic Info
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="expiry-status-tab" data-bs-toggle="tab" data-bs-target="#expiry-status-pane" type="button" role="tab" aria-controls="expiry-status-pane" aria-selected="false">
                    Expiry Status
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content p-4">
            <!-- Basic Info Pane (Existing Content) -->
            <div class="tab-pane fade show active" id="basic-info-pane" role="tabpanel" aria-labelledby="basic-info-tab">
                <div class="row g-4">
                    <!-- Product Image -->
                    <div class="col-md-4">
                        <div class="text-center mb-3" id="productImageContainer">
                            <!-- Image or icon will be rendered here by JavaScript -->
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

            <!-- Expiry Status Pane (New Content) -->
            <div class="tab-pane fade" id="expiry-status-pane" role="tabpanel" aria-labelledby="expiry-status-tab">
                <div id="productExpiryStatusContent">
                    <!-- Content will be loaded by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Update Stock Modal -->
<div class="modal modal-blur fade" id="bulkUpdateStockModal" tabindex="-1"
    aria-labelledby="bulkUpdateStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="bulkUpdateStockModalLabel">
                    <i class="ti ti-packages me-2"></i>
                    Bulk Update Stock
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-info m-3 mb-0">
                    <i class="ti ti-info-circle me-2"></i>
                    <strong>Update stock quantities for selected products.</strong>
                    You can adjust each product's stock individually or apply the same change to all products.
                </div>

                <!-- Bulk Actions Section -->
                <div class="border-bottom p-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-2">Quick Actions</h6>
                            <div class="input-group input-group-sm">
                                <input type="number" id="bulkStockValue" class="form-control"
                                    placeholder="Enter value" min="0">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <span id="bulkActionText">Add to all</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"
                                            onclick="setBulkAction('add', 'Add to all')">Add to all</a></li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="setBulkAction('subtract', 'Subtract from all')">Subtract from
                                            all</a></li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="setBulkAction('set', 'Set all to')">Set all to</a></li>
                                </ul>
                                <button class="btn btn-info" type="button" onclick="applyBulkStockAction()">
                                    Apply
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="ti ti-clock me-1"></i>
                                Changes will be saved when you click "Update Stock"
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Products List -->
                <div id="bulkUpdateStockContent" class="p-3">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading selected products...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <small class="text-muted">
                        <i class="ti ti-info-circle me-1"></i>
                        <span id="updateStockCount">0</span> products selected for update
                    </small>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-info" id="confirmBulkUpdateBtn">
                    <i class="ti ti-packages me-1"></i>
                    Update Stock
                </button>
            </div>
        </div>
    </div>
</div>

<div id="stockUpdateRowTemplate" class="stock-update-row" style="display: none;">
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <div class="product-image-container">
                        <img class="product-image rounded" src="" alt="Product"
                            style="width: 60px; height: 60px; object-fit: cover;">
                    </div>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-1 product-name"></h6>
                    <small class="text-muted product-code"></small>
                </div>
                <div class="col-md-2">
                    <div class="text-center">
                        <div class="fw-semibold current-stock text-primary"></div>
                        <small class="text-muted">Current Stock</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary decrease-btn" type="button">
                            <i class="ti ti-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center new-stock-input" min="0"
                            value="" data-original-stock="">
                        <button class="btn btn-outline-secondary increase-btn" type="button">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                    <small class="text-muted">New Stock</small>
                </div>
                <div class="col-md-2">
                    <div class="text-center">
                        <span class="badge stock-change-badge bg-secondary-lt">No change</span>
                        <small class="text-muted d-block">Change</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.defaultPlaceholderUrl = "{{ asset('img/default_placeholder.png') }}";
</script>