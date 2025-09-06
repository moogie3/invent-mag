import { updateBulkActions } from './partials/recentts/utils/dom.js';
import { searchTransactions } from './partials/recentts/utils/search.js';
import { exportTransactions, bulkExport } from './partials/recentts/utils/export.js';
import { showMarkAsPaidModal, confirmMarkAsPaid } from './partials/recentts/modals/markAsPaid.js';
import { initBulkSelection, bulkMarkAsPaid } from './partials/recentts/bulkActions/selection.js';
import { confirmBulkMarkAsPaid as confirmBulkMarkAsPaidApi } from './partials/recentts/bulkActions/api.js';

// Expose global functions
window.searchTransactions = searchTransactions;
window.exportTransactions = exportTransactions;
window.showMarkAsPaidModal = showMarkAsPaidModal;
window.confirmMarkAsPaid = confirmMarkAsPaid;
window.bulkMarkAsPaid = bulkMarkAsPaid;
window.confirmBulkMarkAsPaid = confirmBulkMarkAsPaidApi;
window.bulkExport = bulkExport;

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
        "confirmBulkMarkPaidBtn"
    );
    if (confirmBulkMarkPaidBtn) {
        confirmBulkMarkPaidBtn.addEventListener("click", confirmBulkMarkAsPaidApi);
    }

    document
        .getElementById("searchInput")
        ?.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                searchTransactions();
            }
        });
});