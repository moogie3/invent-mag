import { initEditCustomerModal } from './partials/customer/editModal/main.js';
import { initCrmCustomerModal } from './partials/customer/crmModal/main.js';

document.addEventListener("DOMContentLoaded", function () {
    initEditCustomerModal();
    initCrmCustomerModal();
});

document.addEventListener('ctrl-s-pressed', function () {
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
});