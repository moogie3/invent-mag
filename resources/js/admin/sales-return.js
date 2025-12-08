import { initBulkSelection, getSelectedSalesReturnIds, clearSalesReturnSelection } from './partials/sales-returns/bulkActions/selection.js';

document.addEventListener('DOMContentLoaded', function () {
    initBulkSelection();
});

window.bulkDeleteSalesReturns = function () {
    const selectedIds = getSelectedSalesReturnIds();
    if (selectedIds.length === 0) {
        alert('Please select at least one sales return to delete.');
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
        alert('Please select at least one sales return to mark as completed.');
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
        alert('Please select at least one sales return to mark as canceled.');
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

