{{-- MODAL --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                <p class="mt-3">Are you sure you want to delete this sales?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- View Sales Modal --}}
<div class="modal modal-blur fade" id="viewSalesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h4 class="modal-title"><i class="ti ti-file-invoice me-2"></i>Sales Order Details</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="viewSalesModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading sales order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto">
                    <small><i class="ti ti-info-circle me-1"></i> View complete sales order details and recheck the
                        invoice</small>
                </div>
                <a href="#" class="btn btn-info" id="salesModalFullView">
                    <i class="ti ti-zoom-scan me-1"></i> Full View
                </a>
                <button type="button" class="btn btn-secondary" id="salesModalPrint">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                <a href="#" class="btn btn-primary" id="salesModalEdit">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
            </div>
        </div>
    </div>
</div>

@if (isset($isPaid) && $isPaid)
    <!-- Paid Invoice Warning Modal -->
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
                    <a href="{{ route('admin.sales.view', $sales->id) }}" class="btn btn-primary w-100">View Invoice</a>
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

<script>
    // Function to load Sales details into modal
    function loadSalesDetails(id) {
        const viewSalesModalContent = document.getElementById('viewSalesModalContent');
        const salesModalEdit = document.getElementById('salesModalEdit');
        const salesModalFullView = document.getElementById('salesModalFullView');

        // Set the edit button URL dynamically
        salesModalEdit.href = `/admin/sales/edit/${id}`;

        // Set the full view button URL dynamically
        salesModalFullView.href = `/admin/sales/view/${id}`;

        // Show loading spinner
        viewSalesModalContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading sales order details...</p>
            </div>
        `;

        // Fetch Sales details via AJAX
        fetch(`/admin/sales/modal-view/${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                viewSalesModalContent.innerHTML = html;
            })
            .catch(error => {
                viewSalesModalContent.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="ti ti-alert-circle me-2"></i> Error loading Sales details: ${error.message}
                    </div>
                `;
            });
    }

    // Print modal content
    document.getElementById('salesModalPrint').addEventListener('click', function() {
        const printContent = document.getElementById('viewSalesModalContent').innerHTML;
        const originalContent = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container print-container">
                <div class="card">
                    <div class="card-body">
                        ${printContent}
                    </div>
                </div>
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContent;

        // Reattach event listeners after restoring original content
        setTimeout(() => {
            // This is a hack to reload the page after printing
            window.location.reload();
        }, 100);
    });
</script>
