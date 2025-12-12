import { SalesReturnCreate } from "./partials/sales-returns/create/SalesReturnCreate.js";
import { SalesReturnEdit } from "./partials/sales-returns/edit/SalesReturnEdit.js";
import { initBulkSelection, getSelectedSalesReturnIds, clearSalesReturnSelection } from './partials/sales-returns/bulkActions/selection.js';

document.addEventListener('DOMContentLoaded', function () {
    const pathname = window.location.pathname;

    if (pathname.includes("/admin/sales-returns/create")) {
        new SalesReturnCreate();
    } else if (pathname.includes("/admin/sales-returns") && pathname.includes("/edit")) {
        new SalesReturnEdit();
    } else if (pathname.includes("/admin/sales-returns")) {
        initBulkSelection();
    }

    const salesReturnDetailModal = document.getElementById('salesReturnDetailModal');
    if (salesReturnDetailModal) {
        salesReturnDetailModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const srId = button.getAttribute('data-sr-id');
            const modalContent = salesReturnDetailModal.querySelector('#salesReturnDetailModalContent');

            const loadingText = window.translations?.loading || 'Loading';
            const closeText = window.translations?.close || 'Close';

            modalContent.innerHTML = `
                <div class="modal-header">
                    <h5 class="modal-title">${loadingText}...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${closeText}"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">${loadingText}...</span>
                        </div>
                    </div>
                </div>
            `;

            fetch(`/admin/sales-returns/${srId}/modal-view`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    modalContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading sales return details:', error);
                    
                    const errorText = window.translations?.error || 'Error';
                    const closeText = window.translations?.close || 'Close';
                    const failedToLoadDetailsText = window.translations?.failed_to_load_details || 'Failed to load details.';

                    modalContent.innerHTML = `
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">${errorText}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${closeText}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger">${failedToLoadDetailsText}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${closeText}</button>
                        </div>
                    `;
                });

            // Set href for Full View and Edit buttons
            const srModalFullView = salesReturnDetailModal.querySelector('#srModalFullView');
            const srModalEdit = salesReturnDetailModal.querySelector('#srModalEdit');
            
            if (srModalFullView) {
                srModalFullView.href = `/admin/sales-returns/${srId}`; // Corrected to match resource route
            }
            if (srModalEdit) {
                srModalEdit.href = `/admin/sales-returns/${srId}/edit`; // Assuming 'edit' route
            }
        });
    }
});

window.bulkDeleteSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        alert(window.translations?.select_one_to_delete || 'Please select at least one sales return to delete.');
        return;
    }

    // Set the count in the modal
    document.getElementById('bulkDeleteCount').textContent = selectedIds.length;

    // Show the modal
    var bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteSalesReturnModal'));
    bulkDeleteModal.show();

    // Handle confirm button click
    document.getElementById('confirmBulkDeleteBtn').onclick = function () {
        fetch('/admin/sales-returns/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids: selectedIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .finally(() => {
                bulkDeleteModal.hide();
            });
    };
}

window.bulkMarkCompletedSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        alert(window.translations?.select_one_to_mark_completed || 'Please select at least one sales return to mark as completed.');
        return;
    }

    // Set the count in the modal
    document.getElementById('bulkCompletedCount').textContent = selectedIds.length;

    // Show the modal
    var bulkMarkCompletedModal = new bootstrap.Modal(document.getElementById('bulkMarkCompletedSalesReturnModal'));
    bulkMarkCompletedModal.show();

    // Handle confirm button click
    document.getElementById('confirmBulkCompletedBtn').onclick = function () {
        fetch('/admin/sales-returns/bulk-complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids: selectedIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .finally(() => {
                bulkMarkCompletedModal.hide();
            });
    };
}

window.bulkMarkCanceledSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        alert(window.translations?.select_one_to_mark_canceled || 'Please select at least one sales return to mark as canceled.');
        return;
    }

    // Set the count in the modal
    document.getElementById('bulkCanceledCount').textContent = selectedIds.length;

    // Show the modal
    var bulkMarkCanceledModal = new bootstrap.Modal(document.getElementById('bulkMarkCanceledSalesReturnModal'));
    bulkMarkCanceledModal.show();

    // Handle confirm button click
    document.getElementById('confirmBulkCanceledBtn').onclick = function () {
        fetch('/admin/sales-returns/bulk-cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids: selectedIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .finally(() => {
                bulkMarkCanceledModal.hide();
            });
    };
}

window.clearSalesReturnSelection = clearSalesReturnSelection;