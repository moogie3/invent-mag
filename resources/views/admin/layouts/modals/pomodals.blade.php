<!-- First, add the modal structure to your pomodals.blade.php file -->
<!-- In admin.layouts.modals.pomodals.blade.php -->

<!-- Delete Modal (keep existing modal) -->
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View PO Modal (new modal) -->
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
            <div class="modal-footer bg-light">
                <div class="text-muted me-auto">
                    <small><i class="ti ti-info-circle me-1"></i> View complete purchase order details without leaving
                        the page</small>
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Close
                </button>
                <button type="button" class="btn btn-primary" id="poModalPrint">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                <a href="#" class="btn btn-success" id="poModalEdit">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Add this script at the bottom of your page or in your JS file -->
<script>
    // Function to set delete form action
    function setDeleteFormAction(url) {
        document.getElementById('deleteForm').action = url;
    }

    // Function to load PO details into modal
    function loadPoDetails(id) {
        const viewPoModalContent = document.getElementById('viewPoModalContent');
        const poModalEdit = document.getElementById('poModalEdit');

        // Set the edit button URL
        poModalEdit.href = `/admin/po/${id}/edit`;

        // Show loading spinner
        viewPoModalContent.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        // Fetch PO details via AJAX
        fetch(`/admin/po/${id}/modal-view`)
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
                    <div class="alert alert-danger">
                        Error loading PO details: ${error.message}
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
