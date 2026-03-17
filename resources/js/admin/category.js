import { handleEditCategoryModal } from './partials/category/handleEditCategoryModal.js';
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    const editCategoryModal = document.getElementById('editCategoryModal');
    if (!editCategoryModal) return;

    handleEditCategoryModal();
    initSelectableTable();

    window.shortcutManager.register('alt+n', () => {
        const modalEl = document.getElementById('createCategoryModal');
        if (modalEl) {
            let createModal = bootstrap.Modal.getInstance(modalEl);
            if (!createModal) {
                createModal = new bootstrap.Modal(modalEl, {});
            }
            if (createModal && !modalEl.classList.contains('show')) {
                createModal.show();
            }
        }
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
