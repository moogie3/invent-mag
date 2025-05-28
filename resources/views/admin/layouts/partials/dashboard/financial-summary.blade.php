<div class="col-md-4 mb-4">
    <div class="card shadow-sm border-1 h-100">
        <div class="card-status-top bg-secondary"></div>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="ti ti-currency-dollar fs-3 me-2"></i> Financial Summary
            </h3>
        </div>
        <div class="card-body p-3">
            @foreach ($financialItems as $item)
                <div class="d-flex align-items-center py-2 border-bottom">
                    <div class="avatar me-3"><i class="ti {{ $item['icon'] }}"></i></div>
                    <div class="flex-fill">{{ $item['label'] }}</div>
                    <div class="fw-semibold">{{ \App\Helpers\CurrencyHelper::format($item['value']) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
