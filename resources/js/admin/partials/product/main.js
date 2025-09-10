import { initModals } from './modals/init.js';
import { initProductModal, loadExpiringSoonProductsModal } from './modals/product.js'; // Import loadExpiringSoonProductsModal
import { initBulkSelection } from './bulkActions/selection.js';
import { initializeSearch } from './search/main.js';
import { initializeEntriesSelector, initKeyboardShortcuts, initExport } from './events.js';

export function initProductPage() {
    document.addEventListener("DOMContentLoaded", function () {
        initModals();
        initProductModal();
        initBulkSelection();
        initializeSearch();
        initializeEntriesSelector();
        initKeyboardShortcuts();
        initExport();

        // Listen for the expiringSoonModal to be shown and fetch data via AJAX
        const expiringSoonModalElement = document.getElementById('expiringSoonModal');
        if (expiringSoonModalElement) {
            expiringSoonModalElement.addEventListener('show.bs.modal', function () {
                // Clear previous content and show loading indicator
                const tableBody = document.getElementById('expiringSoonProductsTableBody');
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading expiring products...
                            </td>
                        </tr>
                    `;
                }

                fetch('/admin/product/expiring-soon') // New endpoint to fetch expiring products
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadExpiringSoonProductsModal(data);
                    })
                    .catch(error => {
                        console.error('Error fetching expiring products:', error);
                        if (tableBody) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="5" class="text-center text-danger py-4">
                                        Error loading products. Please try again.
                                    </td>
                                </tr>
                            `;
                        }
                    });
            });
        }

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
