import { initEditCustomerModal } from './partials/customer/editModal/main.js';
import { initCrmCustomerModal } from './partials/customer/crmModal/main.js';
import { initSelectableTable } from "./layouts/selectable-table.js";

document.addEventListener("DOMContentLoaded", function () {
    initEditCustomerModal();
    initCrmCustomerModal();
    initSelectableTable();

    window.shortcutManager.register('ctrl+s', () => {
        const createModal = document.getElementById('createCustomerModal');
        const editModal = document.getElementById('editCustomerModal');

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
    }, 'Save Customer');

    window.shortcutManager.register('alt+n', () => {
        const createModal = new bootstrap.Modal(document.getElementById('createCustomerModal'));
        createModal.show();
    }, 'New Customer');
});