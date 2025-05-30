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
            selectAll.indeterminate = count > 0 && count < rowCheckboxes.length;
            selectAll.checked = count === rowCheckboxes.length && count > 0;
        }
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

function bulkMarkAsPaid() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);
    if (selected.length === 0) return;

    if (
        confirm(
            "Are you sure you want to mark " +
                selected.length +
                " transactions as paid?"
        )
    ) {
        // Add your bulk update logic here
        console.log("Bulk mark as paid:", selected);
    }
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
