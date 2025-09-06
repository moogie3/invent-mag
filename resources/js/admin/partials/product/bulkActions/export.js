import { getSelectedProductIds } from './selection.js';

export function bulkExportProducts() {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        showToast("Warning", "Please select products to export.", "warning");
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
