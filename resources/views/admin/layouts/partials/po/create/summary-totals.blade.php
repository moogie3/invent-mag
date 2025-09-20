<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h4 class="mb-0"><i class="ti ti-report"></i> {{ __('messages.po_order_summary_title') }}</h4>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">{{ __('messages.po_subtotal') }}</span>
            <span id="subtotal" class="fw-bold">Rp 0</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">{{ __('messages.po_order_discount') }}</span>
            <span id="orderDiscountTotal" class="fw-bold">Rp 0</span>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between">
            <span class="fs-4 fw-bold">{{ __('messages.po_grand_total') }}</span>
            <span id="finalTotal" class="fs-4 fw-bold text-primary">Rp 0</span>
        </div>
    </div>
</div>
