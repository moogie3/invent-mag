import { SalesOrderBulkSelection } from "./SalesOrderBulkSelection.js";

function getSalesSelectedIds() {
    return window.salesBulkSelection
        ? window.salesBulkSelection.getSelectedIds()
        : [];
}

function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

function performBulkDeleteSales(selectedIds, confirmButton, modal) {
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

    fetch("/admin/sales/bulk-delete", {
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
                    "salesOrderBulkDeleteSuccess",
                    `Bulk delete ${
                        data.deleted_count || selectedIds.length
                    } sales order(s) successfully!`,
                );
                location.reload();
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to delete sales orders.",
                    "error",
                );
            }
        })
        .catch((error) => {
            // // console.error("Error:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while deleting sales orders.",
                "error",
            );
        })
        .finally(() => {
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
            modal.hide();
        });
}

export function bulkDeleteSales() {
    const selected = getSalesSelectedIds();
    if (!selected.length) {
        InventMagApp.showToast(
            "Warning",
            "Please select sales orders to delete.",
            "warning",
        );
        return;
    }

    document.getElementById("bulkDeleteCount").textContent = selected.length;
    const modalElement = document.getElementById("bulkDeleteModal");
    const modal = new bootstrap.Modal(modalElement);

    modalElement.addEventListener("hidden.bs.modal", function handler() {
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach((backdrop) => backdrop.remove());

        modalElement.removeEventListener("hidden.bs.modal", handler);
    });

    modal.show();

    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener("click", () =>
        performBulkDeleteSales(selected, newBtn, modal),
    );
}

export function bulkExportSales(exportOption = "csv") {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked"),
    ).map((cb) => cb.value);

    if (selected.length === 0) {
        InventMagApp.showToast(
            "Warning",
            "Please select at least one sales order to export.",
            "warning",
        );
        return;
    }

    const submitBtn = document.querySelector('[onclick="bulkExportSales()"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        submitBtn.disabled = true;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/sales/bulk-export";
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

export function exportAllSales(exportOption = "csv") {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/sales/bulk-export";
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

export function bulkMarkAsPaidSales() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked"),
    );

    if (selected.length === 0) {
        smartSelectUnpaidOnlySales();

        const newSelected = Array.from(
            document.querySelectorAll(".row-checkbox:checked"),
        );

        if (newSelected.length === 0) {
            InventMagApp.showToast(
                "Info",
                "No unpaid sales orders available to mark as paid.",
                "info",
            );
            return;
        }
    } else {
        const selectedPaidSales = selected.filter((checkbox) => {
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

        if (selectedPaidSales.length > 0) {
            selectedPaidSales.forEach((checkbox) => {
                checkbox.checked = false;
                const row = checkbox.closest("tr");
                row.classList.add("table-warning");
                setTimeout(() => {
                    row.classList.remove("table-warning");
                }, 2000);
            });

            if (window.salesBulkSelection) {
                window.salesBulkSelection.updateBulkActionsBar();
            }

            InventMagApp.showToast(
                "Warning",
                `${selectedPaidSales.length} paid sales order(s) were excluded from selection.`,
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

    if (finalSelected.length === 0) {
        InventMagApp.showToast(
            "Info",
            "No unpaid sales orders selected.",
            "info",
        );
        return;
    }

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
            confirmBulkMarkAsPaidSales(
                finalSelected,
                this,
                bulkMarkAsPaidModal,
            );
        });
    }
}

function smartSelectUnpaidOnlySales() {
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

    if (window.salesBulkSelection) {
        window.salesBulkSelection.updateBulkActionsBar();
    }

    if (excludedCount > 0) {
        InventMagApp.showToast(
            "Info",
            `${excludedCount} paid sales order(s) were excluded from selection.`,
            "info",
            3000,
        );
    }
}

function confirmBulkMarkAsPaidSales(selectedIds, confirmButton, modal) {
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

    fetch("/admin/sales/bulk-mark-paid", {
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
                    "salesOrderBulkMarkAsPaidSuccess",
                    `${
                        data.updated_count || selectedIds.length
                    } sales order(s) marked as paid successfully!`,
                );
                location.reload();
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to update sales orders.",
                    "error",
                );
            }
        })
        .catch((error) => {
            // // console.error("Error:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while updating sales orders.",
                "error",
            );
        })
        .finally(() => {
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
        });
}

window.clearSalesSelection = function () {
    if (salesBulkSelection) {
        salesBulkSelection.clearSelection();
    }
};

window.getSalesSelectedIds = getSalesSelectedIds;
window.bulkDeleteSales = bulkDeleteSales;
window.bulkExportSales = bulkExportSales;
window.bulkMarkAsPaidSales = bulkMarkAsPaidSales;
