<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h4 class="fw-semibold mb-3 d-flex align-items-center">
        <i class="ti ti-report me-2 text-primary"></i> {{ __('messages.po_order_summary_title') }}
    </h4>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.po_subtotal') }}</span>
        <span id="subtotal" class="fw-bold">Rp 0</span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.po_order_discount') }}</span>
        <span id="orderDiscountTotal" class="fw-bold">Rp 0</span>
    </div>
    <hr class="my-2 border-primary">
    <div class="d-flex justify-content-between">
        <span class="fs-4 fw-bold">{{ __('messages.po_grand_total') }}</span>
        <span id="finalTotal" class="fs-4 fw-bold text-primary">Rp 0</span>
    </div>
</div>
