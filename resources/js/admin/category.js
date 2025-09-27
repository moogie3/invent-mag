import { handleEditCategoryModal } from './partials/category/handleEditCategoryModal.js';

document.addEventListener("DOMContentLoaded", function () {
    handleEditCategoryModal();
});

document.addEventListener('ctrl-s-pressed', function () {
    const createModal = document.getElementById('createCategoryModal');
    const editModal = document.getElementById('editCategoryModal');

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