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
