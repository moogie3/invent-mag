import { handleEditUnitModal } from './partials/unit/handleEditUnitModal.js';
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    const editUnitModal = document.getElementById('editUnitModal');
    if (!editUnitModal) return;

    handleEditUnitModal();
    initSelectableTable();

    window.shortcutManager.register('alt+n', () => {
        const modalEl = document.getElementById('createUnitModal');
        if (modalEl) {
            let createModal = bootstrap.Modal.getInstance(modalEl);
            if (!createModal) {
                createModal = new bootstrap.Modal(modalEl, {});
            }
            if (createModal && !modalEl.classList.contains('show')) {
                createModal.show();
            }
        }
    }, 'New Unit');

    window.shortcutManager.register('ctrl+s', () => {
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
    }, 'Save Unit');
});
