    <div class="modal modal-blur fade" id="createProductModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Create New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.product.store') }}">
                    @csrf
                    <div class="modal-body">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Product Code</label>
                                <input type="text" class="form-control" name="code" placeholder="Enter code"
                                    required>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" placeholder="Enter name"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock_quantity" placeholder="0"
                                    required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" name="low_stock_threshold"
                                    placeholder="Default (10)" min="1">
                                <small class="form-text text-muted">Leave empty to use system default</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Unit</label>
                                <select class="form-select" name="units_id" required>
                                    <option value="">Select Unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Buying Price</label>
                                <input type="number" step="0" class="form-control" name="price" placeholder="0"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Selling Price</label>
                                <input type="number" step="0" class="form-control" name="selling_price"
                                    placeholder="0" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2" placeholder="Optional description"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="image">
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="has_expiry" name="has_expiry"
                                        value="1">
                                    <label class="form-check-label" for="has_expiry">Product has expiration date</label>
                                </div>
                            </div>

                            <div class="col-md-6 expiry-date-field" style="display: none;">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" id="expiry_date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                    <p class="mt-3">Are you sure you want to delete this product?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Modal -->
    <div class="modal modal-blur fade" id="lowStockModal" tabindex="-1" role="dialog"
        aria-labelledby="lowStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="lowStockModalLabel">Low Stock Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($lowStockProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-vcenter table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lowStockProducts as $product)
                                        @php
                                            // Pass the custom threshold to the helper
                                            [$badgeClass, $badgeText] = \App\Helpers\ProductHelper::getLowStockBadge(
                                                $product->stock_quantity,
                                                $product->low_stock_threshold,
                                            );
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $product->name }}</strong></td>
                                            <td>
                                                @if ($badgeClass)
                                                    <span class="{{ $badgeClass }}">
                                                        {{ $product->stock_quantity }}
                                                    </span>
                                                    @if ($product->low_stock_threshold)
                                                        <small class="d-block text-muted">Threshold:
                                                            {{ $product->low_stock_threshold }}</small>
                                                    @endif
                                                @else
                                                    {{ $product->stock_quantity }}
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.product.edit', $product->id) }}"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="ti ti-edit me-1"></i> Update Stock
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty text-center">
                            <div class="empty-icon">
                                <i class="ti ti-check text-success"></i>
                            </div>
                            <p class="empty-title mt-3">No low stock products found</p>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Expiring Soon Modal -->
    <div class="modal modal-blur fade" id="expiringSoonModal" tabindex="-1" role="dialog"
        aria-labelledby="expiringSoonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="expiringSoonModalLabel">Products Expiring Soon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($expiringSoonProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-vcenter table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Expiry Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expiringSoonProducts as $product)
                                        @php
                                            // Get expiry badge and text
                                            [
                                                $badgeClass,
                                                $badgeText,
                                            ] = \App\Helpers\ProductHelper::getExpiryClassAndText(
                                                $product->expiry_date,
                                            );
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $product->name }}</strong></td>
                                            <td>
                                                {{ $product->expiry_date->format('d-m-Y') }}
                                                @if ($badgeClass)
                                                    <span class="{{ $badgeClass }}">
                                                        {{ $badgeText }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.product.edit', $product->id) }}"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="ti ti-edit me-1"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty text-center">
                            <div class="empty-icon">
                                <i class="ti ti-check text-success"></i>
                            </div>
                            <p class="empty-title mt-3">No products expiring soon</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
