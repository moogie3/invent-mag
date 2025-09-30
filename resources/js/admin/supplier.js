import { initEditSupplierModal } from './partials/supplier/editModal/main.js';
import { initSrmSupplierModal } from './partials/supplier/srmModal/main.js';
import { initSelectableTable } from "./layouts/selectable-table.js";

document.addEventListener("DOMContentLoaded", function () {
    initEditSupplierModal();
    initSrmSupplierModal();
    initSelectableTable();

    window.shortcutManager.register('ctrl+s', () => {
        const createModal = document.getElementById('createSupplierModal');
        const editModal = document.getElementById('editSupplierModal');

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
    }, 'Save Supplier');

    window.shortcutManager.register('alt+n', () => {
        const createModal = new bootstrap.Modal(document.getElementById('createSupplierModal'));
        createModal.show();
    }, 'New Supplier');
});