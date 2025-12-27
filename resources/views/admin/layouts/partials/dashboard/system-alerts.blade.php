<div class="card shadow-sm border-1">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-bell-ringing fs-3 me-2"></i> {{ __('messages.system_alerts') }}
        </h3>
        <a href="{{ route('admin.product') }}" class="btn btn-sm btn-ghost-secondary">{{ __('messages.view_all') }}</a>
    </div>

    <div class="card-body p-0" style="overflow-x: auto;">
        <ul class="nav nav-tabs nav-fill mb-3" id="alertTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="low-stock-tab" data-bs-toggle="tab"
                    data-bs-target="#low-stock-content" type="button" role="tab">
                    <span class="badge bg-danger-lt me-2">{{ $lowStockProducts->count() }}</span> {{ __('messages.low_stock') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="expiring-soon-tab" data-bs-toggle="tab"
                    data-bs-target="#expiring-soon-content" type="button" role="tab">
                    <span class="badge bg-warning-lt me-2">{{ $expiringSoonItems->count() }}</span> {{ __('messages.expiring_soon') }}
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
                                    {{ __('messages.stock_colon') }} <span class="fw-bold text-danger">{{ $product->stock_quantity }}</span>
                                    / {{ __('messages.threshold_colon') }} {{ $product->low_stock_threshold ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">{{ __('messages.no_low_stock_products') }}</div>
                    @endforelse
                </div>
            </div>
            <!-- Expiring Soon Section -->
            <div class="tab-pane fade" id="expiring-soon-content" role="tabpanel">
                <table class="table card-table table-vcenter" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>{{ __('messages.table_product_name') }}</th>
                                <th class="text-center">{{ __('messages.po_id') }}</th>
                                <th class="text-center">{{ __('messages.quantity') }}</th>
                                <th class="text-center">{{ __('messages.table_expiry_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expiringSoonItems as $item)
                                <tr>
                                    <td>{{ $item->product->name }}</td>
                                    <td class="text-center">{{ $item->po_id }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-center">{{ $item->expiry_date->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">{{ __('messages.no_expiring_products') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
