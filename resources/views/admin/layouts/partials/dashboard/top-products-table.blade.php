<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="ti ti-shopping-cart-star fs-3 me-2"></i> Top 5
            Selling
            Products</h3>
    </div>
    <div class="table-responsive" style="min-height: 320px;">
        <table class="table table-vcenter table-hover mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-center">Units Sold</th>
                    <th class="text-end">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topSellingProducts as $product)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm me-2 rounded"
                                    style="background-image: url({{ asset($product->image ?? 'images/placeholder.png') }})"></span>
                                <div>
                                    <div class="fw-semibold">{{ $product->name }}</div>
                                    <div class="small text-muted">{{ $product->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                        <td class="text-center"><span class="badge">{{ $product->units_sold }}</span></td>
                        <td class="text-end fw-medium">
                            {{ \App\Helpers\CurrencyHelper::format($product->revenue) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-3 text-muted">No products data
                            available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('admin.product') }}" class="text-primary">View all products <i
                class="ti ti-arrow-right ms-1"></i></a>
    </div>
</div>
