<div class="col-md-6">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="ti ti-shopping-cart me-2"></i>Shopping Cart
            </h4>
            <span id="cartCount" class="badge bg-green-lt fs-3">0</span>
        </div>
        <div class="card-body">
            <div id="productList" class="list-group">
            </div>
        </div>
        <div class="card-footer p-3">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span id="subtotal" class="fw-bold">Rp 0</span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>Order Discount:</span>
                <div class="input-group" style="max-width: 180px;">
                    <input type="number" id="orderDiscount" class="form-control" value="0" min="0">
                    <select id="discountType" class="form-select" style="max-width: 70px;">
                        <option value="fixed">Rp</option>
                        <option value="percentage">%</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>Tax Rate:</span>
                <div class="input-group" style="max-width: 120px;">
                    <input type="number" id="taxRate" class="form-control" value="0" min="0">
                    <span class="input-group-text">%</span>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between fs-2 fw-bold text-primary">
                <span>Grand Total:</span>
                <span id="finalTotal">Rp 0</span>
            </div>

            <input type="hidden" id="totalDiscountInput" name="total_discount" value="0">
            <input type="hidden" id="orderDiscountInput" name="discount_total" value="0">
            <input type="hidden" id="orderDiscountTypeInput" name="discount_total_type" value="fixed">
            <input type="hidden" id="taxInput" name="tax_amount" value="0">
            <input type="hidden" id="grandTotalInput" name="grand_total" value="0">

            <div class="mt-3">
                <button type="button" id="processPaymentBtn" class="btn btn-primary w-100 btn-lg">
                    <i class="ti ti-cash me-1"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>
