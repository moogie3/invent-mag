import { resetButton } from '../utils/resetButton.js';
import { clearSelection } from '../utils/selection.js';
import { updateBulkActions } from '../utils/dom.js';

export function confirmBulkMarkAsPaid() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    if (selected.length === 0) return;

    const submitBtn = document.getElementById("confirmBulkMarkPaidBtn");
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    submitBtn.disabled = true;

    fetch("/admin/transactions/bulk-mark-paid", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            transaction_ids: selected,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(
                    document.getElementById("bulkMarkAsPaidModal")
                );
                modal.hide();
                modal._element.addEventListener('hidden.bs.modal', function handler() {
                    modal._element.removeEventListener('hidden.bs.modal', handler);
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                });

                InventMagApp.showToast(
                    "Success",
                    `${
                        data.updated_count || selected.length
                    } transaction(s) marked as paid successfully!`,
                    "success"
                );

                clearSelection();

                selected.forEach(id => {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        const statusBadge = row.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Paid';
                            statusBadge.classList.remove('bg-warning', 'bg-danger', 'bg-info');
                            statusBadge.classList.add('bg-success');
                        }
                        const checkbox = row.querySelector('.row-checkbox');
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    }
                });
                updateBulkActions();
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to update transactions.",
                    "error"
                );
            }
        })
        .catch((error) => {
            // // console.error("Error:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while updating the transactions.",
                "error"
            );
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}