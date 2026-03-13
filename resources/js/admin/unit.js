import { handleEditUnitModal } from './partials/unit/handleEditUnitModal.js';
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/settings/unit")) return;
    handleEditUnitModal();
    initSelectableTable();

    window.shortcutManager.register('alt+n', () => {
        const createModal = new bootstrap.Modal(document.getElementById('createUnitModal'));
        createModal.show();
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
