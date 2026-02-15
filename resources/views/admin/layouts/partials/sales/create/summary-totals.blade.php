<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h4 class="card-title mb-4">
        <i class="ti ti-report me-2 text-primary"></i> {{ __('messages.order_summary_title') }}
    </h4>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.sales_subtotal') }}</span>
        <span id="subtotal" class="fw-bold">Rp 0</span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.sales_order_discount') }}</span>
        <span id="orderDiscountTotal" class="fw-bold">Rp 0</span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.tax') }} ({{ $tax->rate ?? 0 }}%)</span>
        <span id="taxTotal" class="fw-bold">Rp 0</span>
    </div>
    <hr class="my-2 border-primary">
    <div class="d-flex justify-content-between">
        <span class="fs-4 fw-bold">{{ __('messages.sales_grand_total') }}</span>
        <span id="finalTotal" class="fs-4 fw-bold text-primary">Rp 0</span>
    </div>
    <input type="hidden" id="taxInput" name="tax_amount" value="0">
</div>
