<div class="modal modal-blur fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Payment Processing</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="mb-3 fs-4">Order Summary</h6>
                        <div class="table-responsive">
                            <table class="table table-borderless table-vcenter">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="modalProductList">
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                        <td class="text-end fw-bold" id="modalSubtotal">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Discount: <span
                                                id="modalDiscountDetail"></span></td>
                                        <td class="text-end" id="modalDiscount">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax (PPN): <span id="modalTaxDetail"></span>
                                        </td>
                                        <td class="text-end" id="modalTax">Rp 0</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-end fw-bold fs-3">Grand Total:</td>
                                        <td class="text-end fw-bold fs-3 text-primary" id="modalGrandTotal">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="ewallet">E-Wallet</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="cashPaymentDiv">
                        <label class="form-label">Amount Received</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="amountReceived" min="0">
                        </div>
                    </div>
                </div>

                <div class="row" id="changeRow">
                    <div class="col-md-12">
                        <div class="alert alert-success mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5">Change:</span>
                                <span class="fw-bold fs-5" id="changeAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-success ms-auto" id="completePaymentBtn">
                    <i class="ti ti-check me-1"></i> Complete Transaction
                </button>
            </div>
        </div>
    </div>
</div>
