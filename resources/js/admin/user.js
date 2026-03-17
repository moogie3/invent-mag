import { fetchRolePermissions } from './partials/user/rolesPermissions/api.js';
import { setupCreateUserModal } from './partials/user/create/main.js';
import { initEditUserModal } from './partials/user/edit/main.js';
import { initEditUserFormSubmission } from './partials/user/edit/api.js';
import { initDeleteUserModal } from './partials/user/delete/main.js';
import { initDeleteUserFormSubmission } from './partials/user/delete/api.js';
import { editUserModal, deleteUserModal, editUserForm, editRolesContainer, editPermissionsContainer } from './partials/user/common/elements.js';
import { resetPermissionStates } from './partials/user/rolesPermissions/sync.js';
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/users")) return;
    fetchRolePermissions()
        .then(() => {
            setupCreateUserModal();
        })
        .catch((error) => {
            console.error("Failed to fetch role permissions:", error);
            setupCreateUserModal();
        });

    initEditUserModal();
    initEditUserFormSubmission();
    initDeleteUserModal();
    initDeleteUserFormSubmission();
    initSelectableTable();

    const editUserModalEl = document.getElementById("editUserModal");
    if (editUserModalEl) {
        editUserModalEl.addEventListener("hidden.bs.modal", function () {
            if (editUserForm) editUserForm.reset();
            if (editRolesContainer) editRolesContainer.innerHTML = "";
            if (editPermissionsContainer) {
                editPermissionsContainer.innerHTML = "";
                resetPermissionStates(editPermissionsContainer);
            }
        });
    }

    const deleteUserModalEl = document.getElementById("deleteUserModal");
    if (deleteUserModalEl) {
        deleteUserModalEl.addEventListener("hidden.bs.modal", function () {
            // currentUserId is handled by the delete/api.js now
        });
    }

    window.shortcutManager.register('alt+n', () => {
        const createModalEl = document.getElementById('createUserModal');
        if (createModalEl) {
            const createModal = new bootstrap.Modal(createModalEl, {});
            createModal.show();
        }
    }, 'New User');

    window.shortcutManager.register('ctrl+s', () => {
        const createModal = document.getElementById('createUserModal');
        const editModal = document.getElementById('editUserModal');

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
    }, 'Save User');
});
