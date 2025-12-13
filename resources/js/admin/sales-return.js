import { SalesReturnCreate } from "./partials/sales-returns/create/SalesReturnCreate.js";
import { SalesReturnEdit } from "./partials/sales-returns/edit/SalesReturnEdit.js";
import { initBulkSelection, getSelectedSalesReturnIds, clearSalesReturnSelection } from './partials/sales-returns/bulkActions/selection.js';

document.addEventListener('DOMContentLoaded', function () {
    const pathname = window.location.pathname;

    if (sessionStorage.getItem("salesReturnBulkDeleteSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("salesReturnBulkDeleteSuccess"),
            "success"
        );
        sessionStorage.removeItem("salesReturnBulkDeleteSuccess");
    }
    if (sessionStorage.getItem("salesReturnBulkCompleteSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("salesReturnBulkCompleteSuccess"),
            "success"
        );
        sessionStorage.removeItem("salesReturnBulkCompleteSuccess");
    }
    if (sessionStorage.getItem("salesReturnBulkCancelSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("salesReturnBulkCancelSuccess"),
            "success"
        );
        sessionStorage.removeItem("salesReturnBulkCancelSuccess");
    }

    if (pathname.includes("/admin/sales-returns/create")) {
        new SalesReturnCreate();
    } else if (pathname.includes("/admin/sales-returns") && pathname.includes("/edit")) {
        new SalesReturnEdit();

        // Logic for srStatusWarningModal
        const editForm = document.getElementById('sales-return-edit-form');
        if (editForm) {
            const isCompletedOrCanceled = editForm.dataset.isCompletedOrCanceled === 'true';
            const status = editForm.dataset.status;

            if (isCompletedOrCanceled) {
                var srStatusWarningModal = new bootstrap.Modal(document.getElementById('srStatusWarningModal'));
                var message = "";

                if (status === 'Completed') {
                    message = window.translations?.sr_modal_completed_warning_message || 'This sales return is completed and cannot be edited.';
                } else if (status === 'Canceled') {
                    message = window.translations?.sr_modal_canceled_warning_message || 'This sales return is canceled and cannot be edited.';
                }

                document.getElementById('srStatusWarningMessage').innerHTML = message;
                srStatusWarningModal.show();

                // Make form fields readonly
                const formElements = document.querySelectorAll('#sales-return-edit-form input, #sales-return-edit-form select, #sales-return-edit-form textarea');
                formElements.forEach(element => {
                    element.setAttribute('readonly', true);
                    if (element.tagName === 'SELECT') {
                        element.setAttribute('disabled', true);
                    }
                });

                // Hide submit buttons
                const submitButtons = document.querySelectorAll('#sales-return-edit-form button[type="submit"]');
                submitButtons.forEach(button => {
                    button.style.display = 'none';
                });
            }
        }

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

        });
    }
});

window.bulkDeleteSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast('Warning', window.translations?.select_one_to_delete || 'Please select at least one sales return to delete.', 'warning');
        return;
    }

    document.getElementById('bulkDeleteCount').textContent = selectedIds.length;

    var bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteSalesReturnModal'));
    bulkDeleteModal.show();

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
                    sessionStorage.setItem('salesReturnBulkDeleteSuccess', data.message || 'Sales returns deleted successfully.');
                    window.location.reload();
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to delete sales returns.', 'error');
                }
            })
            .catch(error => {
                InventMagApp.showToast('Error', 'An error occurred while deleting sales returns.', 'error');
            })
            .finally(() => {
                bulkDeleteModal.hide();
            });
    };
}

window.bulkMarkCompletedSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast('Warning', window.translations?.select_one_to_mark_completed || 'Please select at least one sales return to mark as completed.', 'warning');
        return;
    }

    document.getElementById('bulkCompletedCount').textContent = selectedIds.length;

    var bulkMarkCompletedModal = new bootstrap.Modal(document.getElementById('bulkMarkCompletedSalesReturnModal'));
    bulkMarkCompletedModal.show();

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
                    sessionStorage.setItem('salesReturnBulkCompleteSuccess', data.message || 'Sales returns marked as completed.');
                    window.location.reload();
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to mark sales returns as completed.', 'error');
                }
            })
            .catch(error => {
                InventMagApp.showToast('Error', 'An error occurred.', 'error');
            })
            .finally(() => {
                bulkMarkCompletedModal.hide();
            });
    };
}

window.bulkMarkCanceledSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast('Warning', window.translations?.select_one_to_mark_canceled || 'Please select at least one sales return to mark as canceled.', 'warning');
        return;
    }

    document.getElementById('bulkCanceledCount').textContent = selectedIds.length;

    var bulkMarkCanceledModal = new bootstrap.Modal(document.getElementById('bulkMarkCanceledSalesReturnModal'));
    bulkMarkCanceledModal.show();

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
                    sessionStorage.setItem('salesReturnBulkCancelSuccess', data.message || 'Sales returns marked as canceled.');
                    window.location.reload();
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to mark sales returns as canceled.', 'error');
                }
            })
            .catch(error => {
                InventMagApp.showToast('Error', 'An error occurred.', 'error');
            })
            .finally(() => {
                bulkMarkCanceledModal.hide();
            });
    };
}

window.clearSalesReturnSelection = clearSalesReturnSelection;
