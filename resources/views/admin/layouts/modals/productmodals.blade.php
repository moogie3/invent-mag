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
<div class="modal modal-blur fade" id="lowStockModal" tabindex="-1" role="dialog" aria-labelledby="lowStockModalLabel"
    aria-hidden="true">
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
                                        [$badgeClass, $badgeText] = \App\Helpers\ProductHelper::getExpiryClassAndText(
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
