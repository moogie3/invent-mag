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
});

window.bulkDeletePurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        alert('Please select at least one purchase return to delete.');
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
        alert('Please select at least one purchase return to mark as completed.');
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
        alert('Please select at least one purchase return to mark as canceled.');
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