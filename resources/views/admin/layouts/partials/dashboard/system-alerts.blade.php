<div class="card shadow-sm border-1">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-bell-ringing fs-3 me-2"></i> System Alerts
        </h3>
        <a href="{{ route('admin.product') }}" class="btn btn-sm btn-ghost-secondary">View All</a>
    </div>

    <div class="card-body p-0">
        <ul class="nav nav-tabs nav-fill mb-3" id="alertTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="low-stock-tab" data-bs-toggle="tab"
                    data-bs-target="#low-stock-content" type="button" role="tab">
                    <span class="badge bg-danger-lt me-2">{{ $lowStockProducts->count() }}</span> Low Stock
                </button>
            </li>
        </ul>

        <div class="tab-content px-3 pb-3" id="alertTabContent">
            <!-- Low Stock Section -->
            <div class="tab-pane fade show active" id="low-stock-content" role="tabpanel">
                <div class="shadcn-alert-list">
                    @forelse ($lowStockProducts as $product)
                        <div class="shadcn-alert-item">
                            <div class="shadcn-avatar" style="width: 80px; height: 80px; flex-shrink: 0;">
                                @if (empty($product->image) || strtolower($product->image) === 'null' || strtolower($product->image) === 'undefined' || $product->image === asset('img/default_placeholder.png'))
                                    <i class="ti ti-photo fs-1" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
                                @else
                                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                @endif
                            </div>
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
        </div>
    </div>
</div>
