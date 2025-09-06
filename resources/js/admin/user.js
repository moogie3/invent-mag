import { fetchRolePermissions } from './partials/user/rolesPermissions/api.js';
import { setupCreateUserModal } from './partials/user/create/main.js';
import { initEditUserModal, initEditUserFormSubmission } from './partials/user/edit/main.js';
import { initDeleteUserModal, initDeleteUserFormSubmission } from './partials/user/delete/main.js';
import { editUserModal, deleteUserModal } from './partials/user/common/elements.js';
import { resetPermissionStates } from './partials/user/rolesPermissions/sync.js';

document.addEventListener("DOMContentLoaded", function () {
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

    document
        .getElementById("editUserModal")
        .addEventListener("hidden.bs.modal", function () {
            editUserForm.reset();
            editRolesContainer.innerHTML = "";
            editPermissionsContainer.innerHTML = "";
            resetPermissionStates(editPermissionsContainer);
        });

    document
        .getElementById("deleteUserModal")
        .addEventListener("hidden.bs.modal", function () {
            // currentUserId is handled by the delete/api.js now
            // confirmDeleteBtn.disabled = false;
            // confirmDeleteBtn.textContent = "Delete";
        });
});