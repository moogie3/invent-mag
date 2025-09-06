import { createUserModal, createUserForm, createRolesContainer, createPermissionsContainer } from '../common/elements.js';
import { setupRolePermissionSync, resetPermissionStates } from '../rolesPermissions/sync.js';

export function setupCreateUserModal() {
    if (createRolesContainer && createPermissionsContainer) {
        setupRolePermissionSync(
            createRolesContainer,
            createPermissionsContainer
        );

        const createModalElement =
            document.getElementById("createUserModal");

        createModalElement.addEventListener("shown.bs.modal", function () {
            syncPermissionsFromRoles(
                createRolesContainer,
                createPermissionsContainer
            );
        });

        createModalElement.addEventListener("hidden.bs.modal", function () {
            resetPermissionStates(createPermissionsContainer);
        });
    } else {
        console.error("Create user modal containers not found");
    }
}
