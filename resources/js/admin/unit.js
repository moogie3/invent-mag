import { handleEditUnitModal } from './partials/unit/handleEditUnitModal.js';

document.addEventListener("DOMContentLoaded", function () {
    handleEditUnitModal();
});

document.addEventListener('ctrl-s-pressed', function () {
    const createModal = document.getElementById('createUnitModal');
    const editModal = document.getElementById('editUnitModal');

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