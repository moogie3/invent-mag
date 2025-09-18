<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.po_modal_delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.po_modal_delete_warning') }}
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                {{ __('messages.cancel') }}
                            </button>
                        </div>
                        <div class="col">
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="bulkDeleteModalLabel">
                    <i class="ti ti-trash me-2"></i>
                    {{ __('messages.po_modal_bulk_delete_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-warning d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-alert-circle me-2 fs-4"></i>
                        <div>
                            <strong>{{ __('messages.warning') }}</strong> {{ __('messages.po_modal_bulk_delete_warning_message') }}
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    {{ __('messages.po_modal_bulk_delete_message_part1') }}
                    <strong id="bulkDeleteCount">0</strong>
                    {{ __('messages.po_modal_bulk_delete_message_part2') }}
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> {{ __('messages.po_modal_bulk_delete_what_deleted_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-danger me-1"></i> {{ __('messages.po_modal_bulk_delete_item1') }}</li>
                        <li><i class="ti ti-check text-danger me-1"></i> {{ __('messages.po_modal_bulk_delete_item2') }}</li>
                        <li><i class="ti ti-check text-danger me-1"></i> {{ __('messages.po_modal_bulk_delete_item3') }}</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="ti ti-info-circle me-2 fs-4 mt-1"></i>
                        <div>
                            <strong>{{ __('messages.po_modal_bulk_delete_stock_impact_title') }}</strong>
                            <ul class="mb-0 mt-1 small">
                                <li><strong>{{ __('messages.po_modal_bulk_delete_stock_impact_unpaid') }}</strong> {{ __('messages.po_modal_bulk_delete_stock_impact_unpaid_desc') }}</li>
                                <li><strong>{{ __('messages.po_modal_bulk_delete_stock_impact_paid') }}</strong> {{ __('messages.po_modal_bulk_delete_stock_impact_paid_desc') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>
                        {{ __('messages.cancel') }}
                    </button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                    <i class="ti ti-trash me-1"></i>
                    {{ __('messages.po_modal_bulk_delete_button') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkMarkAsPaidModal" tabindex="-1" aria-labelledby="bulkMarkAsPaidModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
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
                            <strong>{{ __('messages.info') }}</strong> {{ __('messages.po_modal_bulk_mark_paid_info_message') }}
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    {{ __('messages.po_modal_bulk_mark_paid_message_part1') }}
                    <strong id="bulkPaidCount">0</strong>
                    {{ __('messages.po_modal_bulk_mark_paid_message_part2') }}
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> {{ __('messages.po_modal_bulk_mark_paid_what_updated_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i> {{ __('messages.po_modal_bulk_mark_paid_item1') }}</li>
                        <li><i class="ti ti-check text-success me-1"></i> {{ __('messages.po_modal_bulk_mark_paid_item2') }}</li>
                        <li><i class="ti ti-check text-success me-1"></i> {{ __('messages.po_modal_bulk_mark_paid_item3') }}</li>
                        <li><i class="ti ti-check text-success me-1"></i> {{ __('messages.po_modal_bulk_mark_paid_item4') }}</li>
                    </ul>
                </div>

                <p class="mt-3 mb-1 text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ __('messages.po_modal_bulk_mark_paid_note') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-success" id="confirmBulkPaidBtn">
                    <i class="ti ti-check me-1"></i>
                    {{ __('messages.po_modal_bulk_mark_paid_button') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="viewPoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="ti ti-file-invoice me-2"></i>{{ __('messages.po_modal_details_title') }}</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="viewPoModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('messages.loading') }}</span>
                    </div>
                    <p class="mt-3 text-muted">{{ __('messages.po_modal_details_loading_message') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto">
                    <small><i class="ti ti-info-circle me-1"></i> {{ __('messages.po_modal_details_info_message') }}</small>
                </div>
                <a href="#" class="btn btn-info" id="poModalFullView">
                    <i class="ti ti-zoom-scan me-1"></i> {{ __('messages.po_modal_details_full_view_button') }}
                </a>
                <button type="button" class="btn btn-secondary" id="poModalPrint">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.po_modal_details_print_button') }}
                </button>
                <a href="#" class="btn btn-primary" id="poModalEdit">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit') }}
                </a>
            </div>
        </div>
    </div>
</div>

@if (isset($isPaid) && $isPaid)
    <div class="modal fade" id="paidInvoiceModal" tabindex="-1" aria-labelledby="paidInvoiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon text-warning icon-lg mb-4"></i>
                    <h3 class="mb-3">{{ __('messages.warning') }}</h3>
                    <div class="text-secondary">
                        <div class="text-warning text-center">
                            {{ __('messages.po_modal_paid_warning_message1') }}<br>{{ __('messages.po_modal_paid_warning_message2') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.po.view', $pos->id) }}" class="btn btn-primary w-100">{{ __('messages.po_modal_paid_view_invoice_button') }}</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Show the modal automatically when the page loads
            var paidInvoiceModal = new bootstrap.Modal(document.getElementById('paidInvoiceModal'));
            paidInvoiceModal.show();

            // Make form fields readonly if invoice is paid
            const formElements = document.querySelectorAll('input, select, textarea');
            formElements.forEach(element => {
                element.setAttribute('readonly', true);
                if (element.tagName === 'SELECT') {
                    element.setAttribute('disabled', true);
                }
            });

            // Hide any submit buttons
            const submitButtons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(button => {
                button.style.display = 'none';
            });
        });
    </script>
@endif
<!-- Expiring Soon Purchase Orders Modal -->
<div class="modal modal-blur fade" id="expiringPurchaseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h4 class="modal-title"><i class="ti ti-calendar-time me-2"></i>{{ __('messages.po_modal_expiring_title') }}</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>{{ __('messages.table_invoice') }}</th>
                                <th>{{ __('messages.supplier_title') }}</th>
                                <th class="text-center">{{ __('messages.po_due_date') }}</th>
                                <th class="text-end">{{ __('messages.table_total') }}</th>
                                <th class="text-end">{{ __('messages.table_action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="expiringPurchaseTableBody">
                            <!-- Content will be loaded by JavaScript -->
                        </tbody>
                    </table>
                    <div class="text-muted small mt-3">
                        <i class="ti ti-info-circle me-1"></i> {{ __('messages.po_modal_expiring_info_message') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-lt" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>
