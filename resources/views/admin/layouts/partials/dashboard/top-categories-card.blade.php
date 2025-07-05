<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-category fs-3 me-2 text-blue"></i> Top Categories
        </h3>
    </div>
    <div class="card-body p-3" style="min-height: 280px;">
        @forelse ($topCategories ?? [] as $category)
            <div class="d-flex align-items-center py-2">
                <div class="avatar me-3 bg-{{ $loop->iteration % 2 ? 'primary' : 'success' }}-lt">
                    <i class="ti ti-tag"></i>
                </div>
                <div class="flex-fill">
                    <div class="fw-semibold">{{ $category['name'] }}</div>
                    <div class="small text-muted">{{ $category['products_count'] }} products</div>
                </div>
                <div class="text-end">
                    <div class="fw-semibold">
                        {{ \App\Helpers\CurrencyHelper::format($category['revenue']) }}</div>
                    <div class="small text-muted">{{ $category['percentage'] }}%</div>
                </div>
            </div>
        @empty
            <div class="text-center py-3 text-muted">No category data available</div>
        @endforelse
    </div>
</div>
