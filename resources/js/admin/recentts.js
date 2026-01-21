import { updateBulkActions } from "./partials/recentts/utils/dom.js";
import { searchTransactions } from "./partials/recentts/utils/search.js";
import {
    showMarkAsPaidModal,
    confirmMarkAsPaid,
} from "./partials/recentts/modals/markAsPaid.js";
import {
    initBulkSelection,
    bulkMarkAsPaid,
    clearSelection,
} from "./partials/recentts/bulkActions/selection.js";
import { confirmBulkMarkAsPaid as confirmBulkMarkAsPaidApi } from "./partials/recentts/bulkActions/api.js";

function getSelectedTransactionIds() {
    const selectedCheckboxes = document.querySelectorAll(
        ".row-checkbox:checked",
    );
    return Array.from(selectedCheckboxes).map(
        (cb) => `${cb.dataset.type}_${cb.value}`,
    );
}

function bulkExport(exportOption) {
    const selectedIds = getSelectedTransactionIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast(
            "Info",
            "Please select at least one transaction to export.",
            "info",
        );
        return;
    }
    exportRecentTransactions(exportOption, selectedIds);
}

function exportRecentTransactions(exportOption, selectedIds = []) {
    const form = document.createElement("form");
    form.method = "POST";
    // The action is updated to point to the bulk-export route.
    form.action = "/admin/reports/recent-transactions/bulk-export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    if (selectedIds.length === 0) {
        const filterForm = document.getElementById("filterForm");
        if (filterForm) {
            const formData = new FormData(filterForm);
            for (const [key, value] of formData.entries()) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        }
    }

    const searchInput = document.getElementById("searchInput");
    if (searchInput && searchInput.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "search";
        input.value = searchInput.value;
        form.appendChild(input);
    }
    // Changed name to "ids[]" to match controller
    selectedIds.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

// Expose global functions
window.searchTransactions = searchTransactions;
window.showMarkAsPaidModal = showMarkAsPaidModal;
window.confirmMarkAsPaid = confirmMarkAsPaid;
window.bulkMarkAsPaid = bulkMarkAsPaid;
window.confirmBulkMarkAsPaid = confirmBulkMarkAsPaidApi;
window.clearSelection = clearSelection;
window.exportRecentTransactions = exportRecentTransactions;
window.bulkExport = bulkExport;
window.getSelectedTransactionIds = getSelectedTransactionIds;

document.addEventListener("DOMContentLoaded", function () {
    const dateRangeSelect = document.querySelector('select[name="date_range"]');
    const customDateRange = document.getElementById("customDateRange");

    if (dateRangeSelect) {
        dateRangeSelect.addEventListener("change", function () {
            customDateRange.style.display =
                this.value === "custom" ? "block" : "none";
        });
    }

    initBulkSelection();

    const confirmBulkMarkPaidBtn = document.getElementById(
        "confirmBulkMarkPaidBtn",
    );
    if (confirmBulkMarkPaidBtn) {
        confirmBulkMarkPaidBtn.addEventListener(
            "click",
            confirmBulkMarkAsPaidApi,
        );
    }

    document
        .getElementById("searchInput")
        ?.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                searchTransactions();
            }
        });
});
