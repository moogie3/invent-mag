import { getCreateUserModal, createUserForm, createRolesContainer, createPermissionsContainer } from '../common/elements.js';
import { setupRolePermissionSync, resetPermissionStates, syncPermissionsFromRoles } from '../rolesPermissions/sync.js';

export function setupCreateUserModal() {
    const createModalElement = document.getElementById("createUserModal");
    if (!createModalElement) return;
    
    if (createRolesContainer && createPermissionsContainer) {
        setupRolePermissionSync(
            createRolesContainer,
            createPermissionsContainer
        );

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
        console.log("Create user modal containers not found - this is fine on non-user pages");
    }
}
