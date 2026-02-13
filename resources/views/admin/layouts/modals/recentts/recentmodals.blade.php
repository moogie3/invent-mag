<!-- Mark as Paid Modal -->
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-labelledby="markAsPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="markAsPaidModalLabel">
                    <i class="ti ti-check me-2"></i>
                    {{ __('messages.recent_modal_mark_as_paid_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-info d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-info-circle me-2 fs-4"></i>
                        <div>
                            <strong>{{ __('messages.confirm_bang') }}</strong>
                            {{ __('messages.recent_modal_mark_as_paid_message') }}
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    {{ __('messages.recent_modal_review_transaction_details') }}
                </p>

                <div class="bg-light p-3 rounded mb-3">
                    <h6 class="mb-3"><i class="ti ti-file-invoice me-1"></i>
                        {{ __('messages.recent_modal_transaction_details') }}</h6>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>{{ __('messages.table_invoice') }}:</strong></div>
                        <div class="col-sm-8" id="modalInvoice"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>{{ __('messages.type') }}:</strong></div>
                        <div class="col-sm-8" id="modalType"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>{{ __('messages.customer_supplier') }}:</strong></div>
                        <div class="col-sm-8" id="modalCustomerSupplier"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>{{ __('messages.table_amount') }}:</strong></div>
                        <div class="col-sm-8 fw-bold" id="modalAmount"></div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>
                        {{ __('messages.po_modal_bulk_mark_paid_what_updated_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.po_modal_bulk_mark_paid_item1') }}
                        </li>
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.recent_modal_due_amount_cleared') }}
                        </li>
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.po_modal_bulk_mark_paid_item3') }}
                        </li>
                    </ul>
                </div>

                <p class="mt-3 mb-1 text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ __('messages.recent_modal_mark_as_fully_paid_info') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-success" id="confirmMarkPaidBtn" onclick="confirmMarkAsPaid()">
                    <i class="ti ti-check me-1"></i>
                    {{ __('messages.po_modal_bulk_mark_paid_button') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Mark as Paid Modal -->
<div class="modal fade" id="bulkMarkAsPaidModal" tabindex="-1" aria-labelledby="bulkMarkAsPaidModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkMarkAsPaidModalLabel">
                    <i class="ti ti-check me-2"></i>
                    {{ __('messages.po_modal_bulk_mark_paid_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-info d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-info-circle me-2 fs-4"></i>
                        <div>
                            <strong>{{ __('messages.info') }}</strong>
                            {{ __('messages.recent_modal_bulk_mark_as_paid_info') }}
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    {{ __('messages.po_modal_bulk_mark_paid_message_part1') }}
                    <strong id="bulkMarkPaidCount">0</strong>
                    {{ __('messages.recent_modal_bulk_mark_as_paid_message_part2') }}
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>
                        {{ __('messages.po_modal_bulk_mark_paid_what_updated_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.po_modal_bulk_mark_paid_item1') }}
                        </li>
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.recent_modal_due_amounts_cleared') }}
                        </li>
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.po_modal_bulk_mark_paid_item3') }}
                        </li>
                    </ul>
                </div>

                <p class="mt-3 mb-1 text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ __('messages.recent_modal_only_unpaid_affected') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-success" id="confirmBulkMarkPaidBtn">
                    <i class="ti ti-check me-1"></i>
                    {{ __('messages.po_modal_bulk_mark_paid_button') }}
                </button>
            </div>
        </div>
    </div>
</div>
