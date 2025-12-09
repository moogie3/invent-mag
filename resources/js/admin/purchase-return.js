import { PurchaseReturnCreate } from "./partials/purchase-returns/create/PurchaseReturnCreate.js";
import { PurchaseReturnEdit } from "./partials/purchase-returns/edit/PurchaseReturnEdit.js";
import { initBulkSelection, getSelectedPurchaseReturnIds, clearPurchaseReturnSelection } from './partials/purchase-returns/bulkActions/selection.js';

document.addEventListener('DOMContentLoaded', function () {
    const pathname = window.location.pathname;

    if (pathname.includes("/admin/purchase-returns/create")) {
        new PurchaseReturnCreate();
    } else if (pathname.includes("/admin/purchase-returns") && pathname.includes("/edit")) {
        new PurchaseReturnEdit();
    } else if (pathname.includes("/admin/purchase-returns")) {
        initBulkSelection();
    }

    const purchaseReturnDetailModal = document.getElementById('purchaseReturnDetailModal');
    if (purchaseReturnDetailModal) {
        purchaseReturnDetailModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const prId = button.getAttribute('data-pr-id');
            const modalContent = purchaseReturnDetailModal.querySelector('#purchaseReturnDetailModalContent');

            modalContent.innerHTML = `
                <div class="modal-header">
                    <h5 class="modal-title">${window.translations.loading}...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${window.translations.close}"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">${window.translations.loading}...</span>
                        </div>
                    </div>
                </div>
            `;

            fetch(`/admin/purchase-returns/${prId}/modal-view`)
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
                    console.error('Error loading purchase return details:', error);
                    modalContent.innerHTML = `
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">${window.translations.error}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${window.translations.close}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger">${window.translations.failed_to_load_details}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${window.translations.close}</button>
                        </div>
                    `;
                });
        });
    }
});

window.bulkDeletePurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        alert(window.translations.select_one_to_delete);
        return;
    }

    // Set the count in the modal
    document.getElementById('bulkDeleteCount').textContent = selectedIds.length;

    // Show the modal
    var bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeletePurchaseReturnModal'));
    bulkDeleteModal.show();

    // Handle confirm button click
    document.getElementById('confirmBulkDeleteBtn').onclick = function () {
        fetch('/admin/purchase-returns/bulk-delete', {
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

window.bulkMarkCompletedPurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        alert(window.translations.select_one_to_mark_completed);
        return;
    }

    // Set the count in the modal
    document.getElementById('bulkCompletedCount').textContent = selectedIds.length;

    // Show the modal
    var bulkMarkCompletedModal = new bootstrap.Modal(document.getElementById('bulkMarkCompletedPurchaseReturnModal'));
    bulkMarkCompletedModal.show();

    // Handle confirm button click
    document.getElementById('confirmBulkCompletedBtn').onclick = function () {
        fetch('/admin/purchase-returns/bulk-complete', {
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

window.bulkMarkCanceledPurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        alert(window.translations.select_one_to_mark_canceled);
        return;
    }

    // Set the count in the modal
    document.getElementById('bulkCanceledCount').textContent = selectedIds.length;

    // Show the modal
    var bulkMarkCanceledModal = new bootstrap.Modal(document.getElementById('bulkMarkCanceledPurchaseReturnModal'));
    bulkMarkCanceledModal.show();

    // Handle confirm button click
    document.getElementById('confirmBulkCanceledBtn').onclick = function () {
        fetch('/admin/purchase-returns/bulk-cancel', {
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

window.clearPurchaseReturnSelection = clearPurchaseReturnSelection;