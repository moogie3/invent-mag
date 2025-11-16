import { getSelectedProductIds, clearProductSelection } from './selection.js';
import { resetButton } from '../utils/ui.js';
import { fetchProductMetrics } from '../stats.js';

export function bulkDeleteProducts() {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        InventMagApp.showToast("Warning", "Please select products to delete.", "warning");
        return;
    }

    document.getElementById("bulkDeleteCount").textContent = selected.length;
    const modal = new bootstrap.Modal(
        document.getElementById("bulkDeleteModal")
    );
    modal.show();

    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener("click", () =>
        performBulkDelete(selected, newBtn, modal)
    );
}

function performBulkDelete(ids, button, modal) {
    const original = button.innerHTML;
    button.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
    button.disabled = true;

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) {
        InventMagApp.showToast("Error", "Security token not found.", "error");
        resetButton(button, original);
        return;
    }

    fetch("/admin/product/bulk-delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({ ids }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                modal.hide();
                modal._element.addEventListener(
                    "hidden.bs.modal",
                    function handler() {
                        modal._element.removeEventListener(
                            "hidden.bs.modal",
                            handler
                        );
                        InventMagApp.showToast(
                            "Success",
                            `${
                                data.deleted_count || ids.length
                            } products deleted successfully!`,
                            "success"
                        );
                        const backdrops =
                            document.querySelectorAll(".modal-backdrop");
                        backdrops.forEach((backdrop) => backdrop.remove());
                    }
                );
                clearProductSelection();
                ids.forEach((id) => {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }
                });
                fetchProductMetrics();
            } else {
                InventMagApp.showToast("Error", data.message || "Delete failed.", "error");
            }
        })
        .catch((error) => {
            // // console.error("Delete error:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while deleting products.",
                "error"
            );
        })
        .finally(() => {
            resetButton(button, original);
        });
}

window.bulkDeleteProducts = bulkDeleteProducts;