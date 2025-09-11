<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>Are you sure?</h3>
                <div class="text-muted">Do you really want to remove this purchase order? This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                        <div class="col">
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">Delete</button>
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
                    Confirm Bulk Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-warning d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-alert-circle me-2 fs-4"></i>
                        <div>
                            <strong>Warning!</strong> This action cannot be undone.
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    You are about to permanently delete
                    <strong id="bulkDeleteCount">0</strong>
                    purchase order(s) and all associated data.
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> What will be deleted:</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-danger me-1"></i> Purchase order records</li>
                        <li><i class="ti ti-check text-danger me-1"></i> Associated purchase order items</li>
                        <li><i class="ti ti-check text-danger me-1"></i> Related transaction history</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="ti ti-info-circle me-2 fs-4 mt-1"></i>
                        <div>
                            <strong>Stock Level Impact:</strong>
                            <ul class="mb-0 mt-1 small">
                                <li><strong>Unpaid invoices:</strong> Stock levels will be adjusted (reduced)</li>
                                <li><strong>Paid invoices:</strong> Stock levels will remain unchanged</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                    <i class="ti ti-trash me-1"></i>
                    Delete Selected
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
                            <strong>Info!</strong> This will update the payment status of selected purchase orders.
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    You are about to mark
                    <strong id="bulkPaidCount">0</strong>
                    purchase order(s) as fully paid.
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> What will be updated:</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i> Payment status will be set to "Paid"</li>
                        <li><i class="ti ti-check text-success me-1"></i> Outstanding amounts will be cleared</li>
                        <li><i class="ti ti-check text-success me-1"></i> Payment completion date will be recorded</li>
                        <li><i class="ti ti-check text-success me-1"></i> Purchase order status will be updated</li>
                    </ul>
                </div>

                <p class="mt-3 mb-1 text-muted small">
                    <i class="ti ti-info-circle me-1"></i>
                    Only unpaid and partially paid purchase orders will be affected.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmBulkPaidBtn">
                    <i class="ti ti-check me-1"></i>
                    Mark as Paid
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="viewPoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="ti ti-file-invoice me-2"></i>Purchase Order Details</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="viewPoModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading purchase order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto">
                    <small><i class="ti ti-info-circle me-1"></i> View complete purchase order details and recheck the
                        invoice</small>
                </div>
                <a href="#" class="btn btn-info" id="poModalFullView">
                    <i class="ti ti-zoom-scan me-1"></i> Full View
                </a>
                <button type="button" class="btn btn-secondary" id="poModalPrint">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                <a href="#" class="btn btn-primary" id="poModalEdit">
                    <i class="ti ti-edit me-1"></i> Edit
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
                    <h3 class="mb-3">Warning!</h3>
                    <div class="text-secondary">
                        <div class="text-warning text-center">
                            Paid invoices cannot be edited.<br>View mode only.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.po.view', $pos->id) }}" class="btn btn-primary w-100">View Invoice</a>
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
                <h4 class="modal-title"><i class="ti ti-calendar-time me-2"></i>Expiring Soon Purchase Orders</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Supplier</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="expiringPurchaseTableBody">
                            <!-- Content will be loaded by JavaScript -->
                        </tbody>
                    </table>
                    <div class="text-muted small mt-3">
                        <i class="ti ti-info-circle me-1"></i> Purchase orders with a due date within the next 90 days
                        are shown here.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-lt" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
