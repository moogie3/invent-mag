import { handleEditTaxModal } from './partials/tax/handleEditTaxModal.js';
import { handleTaxActions } from './partials/tax/handleTaxActions.js';
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    handleEditTaxModal();
    handleTaxActions();

    window.shortcutManager.register('alt+n', () => {
        const modalEl = document.getElementById('createTaxModal');
        if (modalEl) {
            let createModal = bootstrap.Modal.getInstance(modalEl);
            if (!createModal) {
                createModal = new bootstrap.Modal(modalEl, {});
            }
            if (createModal && !modalEl.classList.contains('show')) {
                createModal.show();
            }
        }
    }, 'New Tax');

    window.shortcutManager.register('ctrl+s', () => {
        const createModal = document.getElementById('createTaxModal');
        const editModal = document.getElementById('editTaxModal');

        if (createModal && createModal.classList.contains('show')) {
            const form = createModal.querySelector('form');
            if (form) form.requestSubmit();
        } else if (editModal && editModal.classList.contains('show')) {
            const form = editModal.querySelector('form');
            if (form) form.requestSubmit();
        }
    }, 'Save Tax');
});
