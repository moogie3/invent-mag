<div class="modal modal-blur fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h3 class="modal-title fw-semibold">Order Summary</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-2">
                <!-- Order Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-borderless">
                        <thead style="background-color: #a7de7c;"
                            class="table-heading text-white text-uppercase rounded-top">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="modalProductList"></tbody>
                        <tfoot class="border-top border-secondary">
                            <tr>
                                <td colspan="3" class="text-end fw-medium">Subtotal:</td>
                                <td class="text-end fw-medium" id="modalSubtotal">Rp 0</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">
                                    Discount <small id="modalDiscountDetail" class="text-muted"></small>:
                                </td>
                                <td class="text-end" id="modalDiscount">Rp 0</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">
                                    Tax (PPN) <small id="modalTaxDetail" class="text-muted"></small>:
                                </td>
                                <td class="text-end" id="modalTax">Rp 0</td>
                            </tr>
                            <tr class="border-top border-2 border-success">
                                <td colspan="3" class="text-end fw-bold fs-4">Grand Total:</td>
                                <td class="text-end fw-bold fs-4 text-primary" id="modalGrandTotal">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

                <!-- Payment Info -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="ewallet">E-Wallet</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="cashPaymentDiv">
                        <label class="form-label fw-semibold">Amount Received</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="amountReceived" min="0">
                            <button type="button" class="btn btn-outline-success" id="exactAmountBtn"
                                title="Exact Amount">
                                <i class="ti ti-equal"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Change -->
                <div class="row mt-4" id="changeRow">
                    <div class="col-md-12">
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <span class="fw-semibold fs-5">Change:</span>
                            <span class="fw-bold fs-5" id="changeAmount">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success ms-auto" id="completePaymentBtn">
                    <i class="ti ti-check me-1"></i> Complete Transaction
                </button>
            </div>
        </div>
    </div>
</div>
