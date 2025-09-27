import { initEditSupplierModal } from './partials/supplier/editModal/main.js';
import { initSrmSupplierModal } from './partials/supplier/srmModal/main.js';


document.addEventListener("DOMContentLoaded", function () {
    initEditSupplierModal();
    initSrmSupplierModal();
});

document.addEventListener('ctrl-s-pressed', function () {
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
});