<ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 border shadow-sm mb-4" role="tablist" style="gap: 5px;">
    <li class="nav-item" role="presentation">
        <a class="nav-link active rounded-2 fw-medium d-flex align-items-center justify-content-center py-2 transition-all" data-bs-toggle="tab" href="#tab-financial" role="tab">
            <i class="ti ti-receipt fs-2 me-2 opacity-75"></i>
            {{ __('messages.purchase_order_sales') }}
            @if(isset($financialNotifications) && $financialNotifications->count() > 0)
                <span class="badge bg-primary text-white ms-2">{{ $financialNotifications->count() }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link rounded-2 fw-medium d-flex align-items-center justify-content-center py-2 transition-all" data-bs-toggle="tab" href="#tab-lowstock" role="tab">
            <i class="ti ti-box fs-2 me-2 opacity-75"></i>
            {{ __('messages.low_stock') }}
            @if(isset($lowStockNotifications) && $lowStockNotifications->count() > 0)
                <span class="badge bg-danger text-white ms-2">{{ $lowStockNotifications->count() }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link rounded-2 fw-medium d-flex align-items-center justify-content-center py-2 transition-all" data-bs-toggle="tab" href="#tab-expiring" role="tab">
            <i class="ti ti-calendar-time fs-2 me-2 opacity-75"></i>
            {{ __('messages.expiring') }}
            @if(isset($expiringNotifications) && $expiringNotifications->count() > 0)
                <span class="badge bg-warning text-white ms-2">{{ $expiringNotifications->count() }}</span>
            @endif
        </a>
    </li>
</ul>

<style>
    .nav-pills .nav-link {
        color: #666;
        background-color: transparent;
    }
    .nav-pills .nav-link:hover:not(.active) {
        background-color: #f8f9fa;
        color: #333;
    }
    .nav-pills .nav-link.active {
        background-color: #f1f5f9;
        color: #206bc4;
        box-shadow: inset 0 0 0 1px rgba(32, 107, 196, 0.1);
    }
    .nav-pills .nav-link.active .opacity-75 {
        opacity: 1 !important;
        color: #206bc4;
    }
    .nav-pills .nav-link.active[href="#tab-lowstock"] {
        color: #d63939;
        background-color: #fcf1f1;
        box-shadow: inset 0 0 0 1px rgba(214, 57, 57, 0.1);
    }
    .nav-pills .nav-link.active[href="#tab-lowstock"] .opacity-75 {
        color: #d63939;
    }
    .nav-pills .nav-link.active[href="#tab-expiring"] {
        color: #f76707;
        background-color: #fef4ec;
        box-shadow: inset 0 0 0 1px rgba(247, 103, 7, 0.1);
    }
    .nav-pills .nav-link.active[href="#tab-expiring"] .opacity-75 {
        color: #f76707;
    }
</style>
