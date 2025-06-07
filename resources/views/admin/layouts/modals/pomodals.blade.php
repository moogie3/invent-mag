<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this Purchase Order?
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary-lt" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
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

<script>
    // Function to load PO details into modal
    function loadPoDetails(id) {
        const viewPoModalContent = document.getElementById('viewPoModalContent');
        const poModalEdit = document.getElementById('poModalEdit');
        const poModalFullView = document.getElementById('poModalFullView');

        // Set the edit button URL dynamically
        poModalEdit.href = `/admin/po/edit/${id}`;

        // Set the full view button URL dynamically
        poModalFullView.href = `/admin/po/view/${id}`;

        // Show loading spinner
        viewPoModalContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading purchase order details...</p>
            </div>
        `;

        // Fetch PO details via AJAX
        fetch(`/admin/po/modal-view/${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                viewPoModalContent.innerHTML = html;
            })
            .catch(error => {
                viewPoModalContent.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="ti ti-alert-circle me-2"></i> Error loading PO details: ${error.message}
                    </div>
                `;
            });
    }

    // Print modal content
    document.getElementById('poModalPrint').addEventListener('click', function() {
        const printContent = document.getElementById('viewPoModalContent').innerHTML;
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
