import { initEditWarehouseModal } from './partials/warehouse/edit/main.js';
import { initCreateWarehouseForm } from './partials/warehouse/create/main.js';
import { initSelectableTable } from "./layouts/selectable-table.js";

document.addEventListener("DOMContentLoaded", function () {
    initEditWarehouseModal();
    initCreateWarehouseForm();
    initSelectableTable();

    window.shortcutManager.register('ctrl+s', () => {
        const createModal = document.getElementById('createWarehouseModal');
        const editModal = document.getElementById('editWarehouseModal');

        if (createModal && createModal.classList.contains('show')) {
            const form = createModal.querySelector('form');
            if (form) {
                form.requestSubmit();
            }
        } else if (editModal && editModal.classList.contains('show')) {
            const form = editModal.querySelector('form');
            if (form) {
                form.requestSubmit();
            }
        }
    }, 'Save Warehouse');

    window.shortcutManager.register('alt+n', () => {
        const createModal = new bootstrap.Modal(document.getElementById('createWarehouseModal'));
        createModal.show();
    }, 'New Warehouse');
});

export function exportWarehouses(exportOption = 'csv') {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/warehouses/export";
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

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

window.exportWarehouses = exportWarehouses;
