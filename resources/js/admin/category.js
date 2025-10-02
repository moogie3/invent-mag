import { handleEditCategoryModal } from './partials/category/handleEditCategoryModal.js';
import { initSelectableTable } from "./layouts/selectable-table.js";

document.addEventListener("DOMContentLoaded", function () {
    handleEditCategoryModal();
    initSelectableTable();

    window.shortcutManager.register('alt+n', () => {
        const createModal = new bootstrap.Modal(document.getElementById('createCategoryModal'));
        createModal.show();
    }, 'New Category');

    window.shortcutManager.register('ctrl+s', () => {
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
    }, 'Save Category');
});
