import { getSelectedProductIds } from "./selection.js";

export function bulkExportProducts(exportOption = "csv") {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        InventMagApp.showToast(
            "Warning",
            "Please select products to export.",
            "warning",
        );
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/product/bulk-export";
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

    selected.forEach((id) => {
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

export function exportAllProducts(exportOption = "csv") {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/product/bulk-export";
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

    // Add filters from the page
    const filters = ["category_id", "warehouse_id", "supplier_id", "units_id"];

    filters.forEach((filter) => {
        const select = document.querySelector(`select[name="${filter}"]`);
        if (select && select.value) {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = filter;
            input.value = select.value;
            form.appendChild(input);
        }
    });

    const searchInput = document.getElementById("searchInput");
    if (searchInput && searchInput.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "search";
        input.value = searchInput.value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

window.bulkExportProducts = bulkExportProducts;
window.exportAllProducts = exportAllProducts;
