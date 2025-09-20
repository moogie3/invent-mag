<div class="col-md-6">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="ti ti-shopping-cart me-2"></i>{{ __('messages.shopping_cart') }}
            </h4>
            <span id="cartCount" class="badge bg-green-lt fs-3">0</span>
        </div>
        <div class="card-body">
            <div id="productList" class="list-group">
            </div>
        </div>
        <div class="card-footer p-3">
            <div class="d-flex justify-content-between mb-2">
                <span>{{ __('messages.subtotal') }}:</span>
                <span id="subtotal" class="fw-bold">{{ \App\Helpers\CurrencyHelper::format(0) }}</span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>{{ __('messages.po_order_discount') }}:</span>
                <div class="input-group" style="max-width: 180px;">
                    <input type="number" id="orderDiscount" class="form-control" value="0" min="0">
                    <select id="discountType" class="form-select" style="max-width: 70px;">
                        <option value="fixed">{{ __('messages.po_fixed') }}</option>
                        <option value="percentage">%</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>{{ __('messages.tax_rate') }}:</span>
                <div class="input-group" style="max-width: 120px;">
                    <input type="number" id="taxRate" class="form-control" value="0" min="0">
                    <span class="input-group-text">%</span>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between fs-2 fw-bold text-success">
                <span>{{ __('messages.po_grand_total') }}:</span>
                <span id="finalTotal">{{ \App\Helpers\CurrencyHelper::format(0) }}</span>
            </div>

            <input type="hidden" id="totalDiscountInput" name="total_discount" value="0">
            <input type="hidden" id="orderDiscountInput" name="discount_total" value="0">
            <input type="hidden" id="orderDiscountTypeInput" name="discount_total_type" value="fixed">
            <input type="hidden" id="taxInput" name="tax_amount" value="0">
            <input type="hidden" id="grandTotalInput" name="grand_total" value="0">

            <div class="mt-3">
                <button type="button" id="processPaymentBtn" class="btn btn-primary w-100 btn-lg">
                    <i class="ti ti-cash me-1"></i> {{ __('messages.process_payment') }}
                </button>
            </div>
        </div>
    </div>
</div>
