document.addEventListener("DOMContentLoaded", function () {
    // Date range toggle
    const dateRangeSelect = document.querySelector('select[name="date_range"]');
    const customDateRange = document.getElementById("customDateRange");

    if (dateRangeSelect) {
        dateRangeSelect.addEventListener("change", function () {
            customDateRange.style.display =
                this.value === "custom" ? "block" : "none";
        });
    }

    // Bulk selection handling
    const selectAll = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    const selectedCount = document.getElementById("selectedCount");

    // Simple select all - no smart filtering
    selectAll?.addEventListener("change", function () {
        rowCheckboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    rowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", updateBulkActions);
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll(".row-checkbox:checked");
        const count = checkedBoxes.length;

        if (selectedCount) selectedCount.textContent = count;

        if (bulkActionsBar) {
            bulkActionsBar.style.display = count > 0 ? "block" : "none";
        }

        if (selectAll) {
            const totalCheckboxes = rowCheckboxes.length;
            const checkedCount = checkedBoxes.length;

            selectAll.indeterminate =
                checkedCount > 0 && checkedCount < totalCheckboxes;
            selectAll.checked =
                totalCheckboxes > 0 && checkedCount === totalCheckboxes;
        }
    }

    const confirmBulkMarkPaidBtn = document.getElementById(
        "confirmBulkMarkPaidBtn"
    );
    if (confirmBulkMarkPaidBtn) {
        confirmBulkMarkPaidBtn.addEventListener("click", confirmBulkMarkAsPaid);
    }
});

function searchTransactions() {
    const searchValue = document.getElementById("searchInput").value;
    const url = new URL(window.location);
    if (searchValue) {
        url.searchParams.set("search", searchValue);
    } else {
        url.searchParams.delete("search");
    }
    window.location.href = url.toString();
}

function exportTransactions() {
    const params = new URLSearchParams(window.location.search);
    params.set("export", "excel");
    // Get the route URL from a data attribute or global variable instead of Blade syntax
    const transactionsRoute =
        document
            .querySelector('meta[name="transactions-route"]')
            ?.getAttribute("content") || "/admin/transactions";
    window.location.href = transactionsRoute + "?" + params.toString();
}

// Global variables for modal
let currentTransactionId = null;
let currentTransactionType = null;

function showMarkAsPaidModal(id, type, invoice, customerSupplier, amount) {
    currentTransactionId = id;
    currentTransactionType = type;

    // Update modal content
    document.getElementById("modalInvoice").textContent = invoice;
    document.getElementById("modalCustomerSupplier").textContent =
        customerSupplier;
    document.getElementById("modalAmount").textContent = amount;
    document.getElementById("modalType").textContent =
        type === "sale" ? "Sales" : "Purchase";

    // Show modal
    const modal = new bootstrap.Modal(
        document.getElementById("markAsPaidModal")
    );
    modal.show();
}

function confirmMarkAsPaid() {
    if (!currentTransactionId || !currentTransactionType) return;

    const submitBtn = document.getElementById("confirmMarkPaidBtn");
    const originalText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    submitBtn.disabled = true;

    fetch("/admin/transactions/" + currentTransactionId + "/mark-paid", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            type: currentTransactionType,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(
                    document.getElementById("markAsPaidModal")
                );
                modal.hide();

                // Show success message
                showToast(
                    "Success",
                    "Transaction marked as paid successfully!",
                    "success"
                );

                // Reload page after short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast("Error: " + data.message, "error");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "An error occurred while updating the transaction.",
                "error"
            );
        })
        .finally(() => {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

function showToast(title, message, type = "info", duration = 4000) {
    // Create a toast container if it doesn't exist
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        toastContainer.className =
            "toast-container position-fixed bottom-0 end-0 p-3";
        toastContainer.style.zIndex = "1050";
        document.body.appendChild(toastContainer);

        // Add animation styles once
        if (!document.getElementById("toast-styles")) {
            const style = document.createElement("style");
            style.id = "toast-styles";
            style.textContent = `
                .toast-enter {
                    transform: translateX(100%);
                    opacity: 0;
                }
                .toast-show {
                    transform: translateX(0);
                    opacity: 1;
                    transition: transform 0.3s ease, opacity 0.3s ease;
                }
                .toast-exit {
                    transform: translateX(100%);
                    opacity: 0;
                    transition: transform 0.3s ease, opacity 0.3s ease;
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Create toast element
    const toast = document.createElement("div");
    toast.className =
        "toast toast-enter align-items-center text-white bg-" +
        getToastColor(type) +
        " border-0";
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong>: ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Force reflow to ensure animation works
    void toast.offsetWidth;

    // Show with animation
    toast.classList.add("toast-show");

    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration,
    });
    bsToast.show();

    // Handle close button clicks
    const closeButton = toast.querySelector(".btn-close");
    closeButton.addEventListener("click", () => {
        hideToast(toast);
    });

    // Auto hide after duration
    const hideTimeout = setTimeout(() => {
        hideToast(toast);
    }, duration);

    // Store timeout on toast element for cleanup
    toast._hideTimeout = hideTimeout;
}

// Helper function to hide toast with animation
function hideToast(toast) {
    // Clear any existing timeout
    if (toast._hideTimeout) {
        clearTimeout(toast._hideTimeout);
    }

    // Add exit animation
    toast.classList.remove("toast-show");
    toast.classList.add("toast-exit");

    // Remove after animation completes
    setTimeout(() => {
        toast.remove();
    }, 300);
}

// Helper function to get the appropriate Bootstrap color class
function getToastColor(type) {
    switch (type) {
        case "success":
            return "success";
        case "error":
            return "danger";
        case "warning":
            return "warning";
        default:
            return "info";
    }
}

function clearSelection() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
    });
    document.getElementById("selectAll").checked = false;
    document.getElementById("bulkActionsBar").style.display = "none";
}

// Smart selection function - only called when bulk mark as paid is clicked
function smartSelectUnpaidOnly() {
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const selectAll = document.getElementById("selectAll");
    let excludedCount = 0;

    rowCheckboxes.forEach((checkbox) => {
        // Get the transaction status from the row
        const row = checkbox.closest("tr");
        const statusBadge = row.querySelector(".badge");
        const status = statusBadge ? statusBadge.textContent.trim() : "";

        // Only select if status is not 'Paid'
        if (status === "Paid") {
            checkbox.checked = false;
            // Add visual feedback for excluded items
            row.classList.add("table-warning");
            setTimeout(() => {
                row.classList.remove("table-warning");
            }, 2000);
            excludedCount++;
        } else {
            checkbox.checked = true;
        }
    });

    // Update bulk actions
    updateBulkActions();

    // Show notification if some items were excluded
    if (excludedCount > 0) {
        showToast(
            "Info",
            `${excludedCount} paid transaction(s) were excluded from selection.`,
            "info",
            3000
        );
    }
}

// Updated bulkMarkAsPaid function with smart selection
function bulkMarkAsPaid() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    );

    // If no items are selected, perform smart selection first
    if (selected.length === 0) {
        smartSelectUnpaidOnly();

        // Recheck selected items after smart selection
        const newSelected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );

        if (newSelected.length === 0) {
            showToast(
                "Info",
                "No unpaid transactions available to mark as paid.",
                "info"
            );
            return;
        }
    } else {
        // Check if any selected transactions are already paid
        const selectedPaidTransactions = selected.filter((checkbox) => {
            const row = checkbox.closest("tr");
            const statusBadge = row.querySelector(".badge");
            const status = statusBadge ? statusBadge.textContent.trim() : "";
            return status === "Paid";
        });

        if (selectedPaidTransactions.length > 0) {
            // Uncheck paid transactions and show warning
            selectedPaidTransactions.forEach((checkbox) => {
                checkbox.checked = false;
                const row = checkbox.closest("tr");
                row.classList.add("table-warning");
                setTimeout(() => {
                    row.classList.remove("table-warning");
                }, 2000);
            });

            updateBulkActions();

            showToast(
                "Warning",
                `${selectedPaidTransactions.length} paid transaction(s) were excluded from selection.`,
                "warning"
            );

            // Check if any unpaid transactions remain selected
            const remainingSelected = Array.from(
                document.querySelectorAll(".row-checkbox:checked")
            );

            if (remainingSelected.length === 0) {
                return;
            }
        }
    }

    // Get final selected count
    const finalSelected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    // Update the count in the modal
    document.getElementById("bulkMarkPaidCount").textContent =
        finalSelected.length;

    // Show the modal
    const modal = new bootstrap.Modal(
        document.getElementById("bulkMarkAsPaidModal")
    );
    modal.show();
}

// Helper function to update bulk actions (moved outside DOMContentLoaded for global access)
function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll(".row-checkbox:checked");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    const selectedCount = document.getElementById("selectedCount");
    const selectAll = document.getElementById("selectAll");
    const count = checkedBoxes.length;

    if (selectedCount) selectedCount.textContent = count;

    if (bulkActionsBar) {
        bulkActionsBar.style.display = count > 0 ? "block" : "none";
    }

    if (selectAll) {
        const totalCheckboxes = rowCheckboxes.length;
        const checkedCount = checkedBoxes.length;

        selectAll.indeterminate =
            checkedCount > 0 && checkedCount < totalCheckboxes;
        selectAll.checked =
            totalCheckboxes > 0 && checkedCount === totalCheckboxes;
    }
}

// Add the confirm bulk mark as paid function
function confirmBulkMarkAsPaid() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    if (selected.length === 0) return;

    const submitBtn = document.getElementById("confirmBulkMarkPaidBtn");
    const originalText = submitBtn.innerHTML;

    // Show loading state
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
                // Close modal
                const modal = bootstrap.Modal.getInstance(
                    document.getElementById("bulkMarkAsPaidModal")
                );
                modal.hide();

                // Show success message
                showToast(
                    "Success",
                    `${
                        data.updated_count || selected.length
                    } transaction(s) marked as paid successfully!`,
                    "success"
                );

                // Clear selection
                clearSelection();

                // Reload page after short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to update transactions.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                "An error occurred while updating the transactions.",
                "error"
            );
        })
        .finally(() => {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

function bulkExport() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);
    if (selected.length === 0) return;

    const params = new URLSearchParams(window.location.search);
    params.set("export", "excel");
    params.set("selected", selected.join(","));
    // Get the route URL from a data attribute or global variable instead of Blade syntax
    const transactionsRoute =
        document
            .querySelector('meta[name="transactions-route"]')
            ?.getAttribute("content") || "/admin/transactions";
    window.location.href = transactionsRoute + "?" + params.toString();
}

// Enter key search
document
    .getElementById("searchInput")
    ?.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            searchTransactions();
        }
    });
