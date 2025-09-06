import { originalTableContent, setOriginalTableContent, originalWarehouseData } from './state.js';

export function storeOriginalTable() {
    if (!originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            setOriginalTableContent(tableBody.innerHTML);

            const rows = tableBody.querySelectorAll("tr[data-id]");
            rows.forEach((row) => {
                const warehouseId = row.dataset.id;
                const warehouseData = extractWarehouseDataFromRow(row);
                if (warehouseData) {
                    originalWarehouseData.set(warehouseId, warehouseData);
                }
            });
        }
    }
}

export function extractWarehouseDataFromRow(row) {
    try {
        const nameElement = row.querySelector(".sort-name");
        const addressElement = row.querySelector(".sort-address");
        const descriptionElement = row.querySelector(".sort-description");
        const isMainElement = row.querySelector(".sort-is-main");

        if (!nameElement) return null;

        return {
            id: parseInt(row.dataset.id),
            name: nameElement.textContent.trim(),
            address: addressElement?.textContent?.trim() || "N/A",
            description: descriptionElement?.textContent?.trim() || "N/A",
            is_main: isMainElement?.textContent?.trim() === "Main",
        };
    } catch (error) {
        console.error("Error extracting warehouse data:", error);
        return null;
    }
}

export function restoreOriginalTable() {
    if (originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            tableBody.innerHTML = originalTableContent;
        }
    }
}
