import { initEditWarehouseModal } from './partials/warehouse/edit/main.js';
import { initCreateWarehouseForm } from './partials/warehouse/create/main.js';


document.addEventListener("DOMContentLoaded", function () {
    initEditWarehouseModal();
    initCreateWarehouseForm();
});

document.addEventListener('ctrl-s-pressed', function () {
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
});
