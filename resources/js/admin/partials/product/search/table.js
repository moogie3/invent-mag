import { originalTableContent, setOriginalTableContent, originalProductData } from './state.js';
import { extractProductDataFromRow } from '../utils/helpers.js';
import { initBulkSelection, restoreCheckboxStates, updateBulkActionsBarVisibility } from '../bulkActions/selection.js';

export function storeOriginalTable() {
    if (!originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            setOriginalTableContent(tableBody.innerHTML);

            const rows = tableBody.querySelectorAll("tr[data-id]");
            rows.forEach((row) => {
                const productId = row.dataset.id;
                const productData = extractProductDataFromRow(row);
                if (productData) {
                    originalProductData.set(productId, productData);
                }
            });
        }
    }
}

export function restoreOriginalTable() {
    if (originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            tableBody.innerHTML = originalTableContent;
            setTimeout(() => {
                initBulkSelection();
                restoreCheckboxStates();
                updateBulkActionsBarVisibility();
            }, 100);
        }
    }
}
