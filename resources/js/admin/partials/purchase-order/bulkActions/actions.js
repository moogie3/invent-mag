import { PurchaseOrderBulkSelection } from "./PurchaseOrderBulkSelection.js";

function getSelectedIds() {
    return window.bulkSelection ? window.bulkSelection.getSelectedIds() : [];
}

function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

function performBulkDelete(selectedIds, confirmButton, modal) {
    if (!selectedIds || selectedIds.length === 0) return;

    const originalText = confirmButton.innerHTML;
    confirmButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Deleting...
        `;
    confirmButton.disabled = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        // // console.error("CSRF token not found");
        InventMagApp.showToast(
            "Error",
            "Security token not found. Please refresh the page.",
            "error",
        );
        resetButton(confirmButton, originalText);
        return;
    }

    fetch("/admin/po/bulk-delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            ids: selectedIds,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                sessionStorage.setItem(
                    "purchaseOrderBulkDeleteSuccess",
                    `Bulk delete ${
                        data.deleted_count || selectedIds.length
                    } purchase order(s) successfully!`,
                );
                location.reload();
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to delete purchase orders.",
                    "error",
                );
            }
        })
        .catch((error) => {
            // // console.error("Error:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while deleting purchase orders.",
                "error",
            );
        })
        .finally(() => {
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
            modal.hide();
        });
}

export function bulkDeletePO() {
    const selected = getSelectedIds();
    if (!selected.length) {
        InventMagApp.showToast(
            "Warning",
            "Please select purchase orders to delete.",
            "warning",
        );
        return;
    }

    document.getElementById("bulkDeleteCount").textContent = selected.length;
    const modal = new bootstrap.Modal(
        document.getElementById("bulkDeleteModal"),
    );
    modal.show();

    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener("click", () =>
        performBulkDelete(selected, newBtn, modal),
    );
}

export function bulkExportPO(exportOption = "csv") {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked"),
    ).map((cb) => cb.value);

    if (selected.length === 0) {
        InventMagApp.showToast(
            "Warning",
            "Please select at least one purchase order to export.",
            "warning",
        );
        return;
    }

    const submitBtn = document.querySelector('[onclick="bulkExportPO()"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        submitBtn.disabled = true;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/po/bulk-export";
    form.style.display = "none";

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement("input");
        csrfInput.type = "hidden";
        csrfInput.name = "_token";
        csrfInput.value = csrfToken.getAttribute("content");
        form.appendChild(csrfInput);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    selected.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();

    setTimeout(() => {
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        document.body.removeChild(form);
    }, 2000);
}

export function exportAllPurchases(exportOption = "csv") {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/po/bulk-export";
    form.style.display = "none";

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement("input");
        csrfInput.type = "hidden";
        csrfInput.name = "_token";
        csrfInput.value = csrfToken.getAttribute("content");
        form.appendChild(csrfInput);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    // Add filters from the page
    const monthSelect = document.querySelector('select[name="month"]');
    const yearSelect = document.querySelector('select[name="year"]');
    const searchInput = document.getElementById("searchInput");

    if (monthSelect && monthSelect.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "month";
        input.value = monthSelect.value;
        form.appendChild(input);
    }

    if (yearSelect && yearSelect.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "year";
        input.value = yearSelect.value;
        form.appendChild(input);
    }

    if (searchInput && searchInput.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "search";
        input.value = searchInput.value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();

    setTimeout(() => {
        document.body.removeChild(form);
    }, 2000);
}

export function bulkMarkAsPaidPO() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked"),
    );

    if (selected.length === 0) {
        smartSelectUnpaidOnlyPO();

        const newSelected = Array.from(
            document.querySelectorAll(".row-checkbox:checked"),
        );

        if (newSelected.length === 0) {
            InventMagApp.showToast(
                "Info",
                "No unpaid purchase orders available to mark as paid.",
                "info",
            );
            return;
        }
    } else {
        const selectedPaidPOs = selected.filter((checkbox) => {
            const row = checkbox.closest("tr");

            let statusElement = row.querySelector(".sort-status span");

            if (!statusElement) {
                const statusCell = row.querySelector(".sort-status");
                if (statusCell) {
                    statusElement = statusCell.querySelector("span");
                }
            }

            if (!statusElement) {
                statusElement = row.querySelector(".badge");
            }

            const status = statusElement
                ? statusElement.textContent.trim()
                : "";

            return statusElement?.classList.contains("bg-green-lt");
        });

        if (selectedPaidPOs.length > 0) {
            selectedPaidPOs.forEach((checkbox) => {
                checkbox.checked = false;
                const row = checkbox.closest("tr");
                row.classList.add("table-warning");
                setTimeout(() => {
                    row.classList.remove("table-warning");
                }, 2000);
            });

            if (window.bulkSelection) {
                window.bulkSelection.updateBulkActionsBar();
            }

            InventMagApp.showToast(
                "Warning",
                `${selectedPaidPOs.length} paid purchase order(s) were excluded from selection.`,
                "warning",
            );

            const remainingSelected = Array.from(
                document.querySelectorAll(".row-checkbox:checked"),
            );

            if (remainingSelected.length === 0) {
                return;
            }
        }
    }

    const finalSelected = Array.from(
        document.querySelectorAll(".row-checkbox:checked"),
    ).map((cb) => cb.value);

    const bulkPaidCount = document.getElementById("bulkPaidCount");
    if (bulkPaidCount) {
        bulkPaidCount.textContent = finalSelected.length;
    }

    const bulkMarkAsPaidModal = new bootstrap.Modal(
        document.getElementById("bulkMarkAsPaidModal"),
    );
    bulkMarkAsPaidModal.show();

    const confirmBtn = document.getElementById("confirmBulkPaidBtn");
    if (confirmBtn) {
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function () {
            confirmBulkMarkAsPaidPO(finalSelected, this, bulkMarkAsPaidModal);
        });
    }
}

function smartSelectUnpaidOnlyPO() {
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    let excludedCount = 0;

    rowCheckboxes.forEach((checkbox) => {
        const row = checkbox.closest("tr");

        let statusElement = row.querySelector(".sort-status span");

        if (!statusElement) {
            const statusCell = row.querySelector(".sort-status");
            if (statusCell) {
                statusElement = statusCell.querySelector("span");
            }
        }

        if (!statusElement) {
            statusElement = row.querySelector(".badge");
        }

        const status = statusElement ? statusElement.textContent.trim() : "";

        const isPaid = statusElement?.classList.contains("bg-green-lt");

        if (isPaid) {
            checkbox.checked = false;
            row.classList.add("table-warning");
            setTimeout(() => {
                row.classList.remove("table-warning");
            }, 2000);
            excludedCount++;
        } else {
            checkbox.checked = true;
        }
    });

    if (window.bulkSelection) {
        window.bulkSelection.updateBulkActionsBar();
    }

    if (excludedCount > 0) {
        InventMagApp.showToast(
            "Info",
            `${excludedCount} paid purchase order(s) were excluded from selection.`,
            "info",
            3000,
        );
    }
}

function confirmBulkMarkAsPaidPO(selectedIds, confirmButton, modal) {
    if (!selectedIds || selectedIds.length === 0) return;

    const originalText = confirmButton.innerHTML;
    confirmButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Processing...
        `;
    confirmButton.disabled = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        // // console.error("CSRF token not found");
        InventMagApp.showToast(
            "Error",
            "Security token not found. Please refresh the page.",
            "error",
        );
        resetButton(confirmButton, originalText);
        return;
    }

    fetch("/admin/po/bulk-mark-paid", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            ids: selectedIds,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                sessionStorage.setItem(
                    "purchaseOrderBulkMarkAsPaidSuccess",
                    `${
                        data.updated_count || selectedIds.length
                    } purchase order(s) marked as paid successfully!`,
                );
                location.reload();
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to update purchase orders.",
                    "error",
                );
            }
        })
        .catch((error) => {
            // // console.error("Error:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while updating purchase orders.",
                "error",
            );
        })
        .finally(() => {
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
            modal.hide();
        });
}

window.clearPOSelection = function () {
    if (bulkSelection) {
        bulkSelection.clearSelection();
    }
};

window.getSelectedIds = getSelectedIds;
window.bulkDeletePO = bulkDeletePO;
window.bulkExportPO = bulkExportPO;
window.bulkMarkAsPaidPO = bulkMarkAsPaidPO;
