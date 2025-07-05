



<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-bell-ringing fs-3 me-2"></i> System Alerts
        </h3>
        <a href="{{ route('admin.product') }}" class="btn btn-sm btn-ghost-secondary">View All</a>
    </div>

    <div class="card-body p-0">
        <ul class="nav nav-tabs nav-fill mb-3" id="alertTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="low-stock-tab" data-bs-toggle="tab" data-bs-target="#low-stock-content" type="button" role="tab">
                    <span class="badge bg-danger-lt me-2">{{ $lowStockProducts->count() }}</span> Low Stock
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="expiring-soon-tab" data-bs-toggle="tab" data-bs-target="#expiring-soon-content" type="button" role="tab">
                    <span class="badge bg-warning-lt me-2">{{ $expiringSoonProducts->count() }}</span> Expiring Soon
                </button>
            </li>
        </ul>

        <div class="tab-content px-3 pb-3" id="alertTabContent">
            <!-- Low Stock Section -->
            <div class="tab-pane fade show active" id="low-stock-content" role="tabpanel">
                <div class="shadcn-alert-list">
                    @forelse ($lowStockProducts as $product)
                        <div class="shadcn-alert-item">
                            <div class="shadcn-avatar" style="background-image: url('{{ $product->image }}')"></div>
                            <div class="shadcn-product-info">
                                <div class="shadcn-product-name" title="{{ $product->name }}">{{ $product->name }}</div>
                                <div class="shadcn-product-details">
                                    Stock: <span class="fw-bold text-danger">{{ $product->stock_quantity }}</span>
                                    / Threshold: {{ $product->low_stock_threshold ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No low stock products.</div>
                    @endforelse
                </div>
            </div>

            <!-- Expiring Soon Section -->
            <div class="tab-pane fade" id="expiring-soon-content" role="tabpanel">
                <div class="shadcn-alert-list">
                    @forelse ($expiringSoonProducts as $product)
                        <div class="shadcn-alert-item">
                            <div class="shadcn-avatar" style="background-image: url('{{ $product->image }}')"></div>
                            <div class="shadcn-product-info">
                                <div class="shadcn-product-name" title="{{ $product->name }}">{{ $product->name }}</div>
                                <div class="shadcn-product-details">
                                    Expires: <span class="fw-bold text-warning">{{ optional($product->expiry_date)->format('d M Y') ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No expiring soon products.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
