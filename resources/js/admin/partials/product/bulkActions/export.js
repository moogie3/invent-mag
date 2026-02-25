import { getSelectedProductIds } from './selection.js';

export function bulkExportProducts(exportOption = 'csv') {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        InventMagApp.showToast("Warning", "Please select products to export.", "warning");
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

window.bulkExportProducts = bulkExportProducts;