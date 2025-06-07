<!-- Mark as Paid Modal -->
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-labelledby="markAsPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="markAsPaidModalLabel">
                    <i class="ti ti-check me-2"></i>
                    Mark Transaction as Paid
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-info d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-info-circle me-2 fs-4"></i>
                        <div>
                            <strong>Confirm!</strong> You are about to mark this transaction as fully paid.
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    Review the transaction details below before confirming.
                </p>

                <div class="bg-light p-3 rounded mb-3">
                    <h6 class="mb-3"><i class="ti ti-file-invoice me-1"></i> Transaction Details:</h6>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Invoice:</strong></div>
                        <div class="col-sm-8" id="modalInvoice"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Type:</strong></div>
                        <div class="col-sm-8" id="modalType"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Customer/Supplier:</strong></div>
                        <div class="col-sm-8" id="modalCustomerSupplier"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Amount:</strong></div>
                        <div class="col-sm-8 fw-bold" id="modalAmount"></div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> What will be updated:</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i> Payment status will be set to "Paid"</li>
                        <li><i class="ti ti-check text-success me-1"></i> Due amount will be cleared</li>
                        <li><i class="ti ti-check text-success me-1"></i> Payment completion date will be recorded</li>
                    </ul>
                </div>

                <p class="mt-3 mb-1 text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    This action will mark the transaction as fully paid.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmMarkPaidBtn" onclick="confirmMarkAsPaid()">
                    <i class="ti ti-check me-1"></i>
                    Mark as Paid
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Mark as Paid Modal -->
<div class="modal fade" id="bulkMarkAsPaidModal" tabindex="-1" aria-labelledby="bulkMarkAsPaidModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkMarkAsPaidModalLabel">
                    <i class="ti ti-check me-2"></i>
                    Confirm Bulk Mark as Paid
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-info d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-info-circle me-2 fs-4"></i>
                        <div>
                            <strong>Info!</strong> This will update the payment status of selected transactions.
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    You are about to mark
                    <strong id="bulkMarkPaidCount">0</strong>
                    transaction(s) as fully paid.
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> What will be updated:</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i> Payment status will be set to "Paid"
                        </li>
                        <li><i class="ti ti-check text-success me-1"></i> Due amounts will be cleared</li>
                        <li><i class="ti ti-check text-success me-1"></i> Payment completion date will be
                            recorded
                        </li>
                    </ul>
                </div>

                <p class="mt-3 mb-1 text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    Only unpaid and partially paid transactions will be affected.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmBulkMarkPaidBtn">
                    <i class="ti ti-check me-1"></i>
                    Mark as Paid
                </button>
            </div>
        </div>
    </div>
</div>
