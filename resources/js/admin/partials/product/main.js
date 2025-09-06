import { initModals } from './modals/init.js';
import { initProductModal } from './modals/product.js';
import { initBulkSelection } from './bulkActions/selection.js';
import { initializeSearch } from './search/main.js';
import { initExpiryCheckbox, initFlatpickr, initializeEntriesSelector, initKeyboardShortcuts, initExport } from './events.js';

export function initProductPage() {
    window.addEventListener("load", function () {
        initModals();
        initExpiryCheckbox();
        initFlatpickr();
        initProductModal();
        initBulkSelection();
        initializeSearch();
        initializeEntriesSelector();
        initKeyboardShortcuts();
        initExport();

        const searchInput = document.getElementById("searchInput");
        if (searchInput && !searchInput.hasAttribute("data-search-initialized")) {
            initializeSearch();
            searchInput.setAttribute("data-search-initialized", "true");
        }

        const selectAllCheckbox = document.getElementById("selectAll");
        if (
            selectAllCheckbox &&
            !selectAllCheckbox.hasAttribute("data-bulk-initialized")
        ) {
            initBulkSelection();
            selectAllCheckbox.setAttribute("data-bulk-initialized", "true");
        }

        if (typeof selectedProductIds === "undefined") {
            window.selectedProductIds = new Set();
        }
    });
}
