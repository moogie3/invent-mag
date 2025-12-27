import { originalTableContent, setOriginalTableContent, originalSupplierData } from './state.js';

export function storeOriginalTable() {
    if (!originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            setOriginalTableContent(tableBody.innerHTML);

            const rows = tableBody.querySelectorAll("tr[data-id]");
            rows.forEach((row) => {
                const supplierId = row.dataset.id;
                const supplierData = extractSupplierDataFromRow(row);
                if (supplierData) {
                    originalSupplierData.set(supplierId, supplierData);
                }
            });
        }
    }
}

export function extractSupplierDataFromRow(row) {
    try {
        const codeElement = row.querySelector(".sort-code");
        const nameElement = row.querySelector(".sort-name");
        const addressElement = row.querySelector(".sort-address");
        const locationElement = row.querySelector(".sort-location");
        const paymentTermsElement = row.querySelector(".sort-paymentterms");
        const emailElement = row.querySelector(".sort-email");
        const imageElement = row.querySelector(".sort-image img");

        if (!nameElement) return null;

        return {
            id: parseInt(row.dataset.id),
            code: codeElement?.textContent?.trim() || "N/A",
            name: nameElement.textContent.trim(),
            address: addressElement?.textContent?.trim() || "N/A",
            location: locationElement?.textContent?.trim() || "N/A",
            payment_terms:
                paymentTermsElement?.textContent?.trim() || "N/A",
            email: emailElement?.textContent?.trim() || "N/A",
            image: imageElement?.src || "",
        };
    } catch (error) {
        console.error("Error extracting supplier data:", error);
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
