import { getEditUserModal, getEditUserForm, getEditUserIdInput, getEditNameInput, getEditEmailInput, getEditRolesContainer, getEditPermissionsContainer } from '../common/elements.js';
import { syncPermissionsFromRoles, setupRolePermissionSync } from '../rolesPermissions/sync.js';

export function initEditUserModal() {
    const editUserModalEl = document.getElementById("editUserModal");
    if (!editUserModalEl) return;
    
    document.querySelectorAll(".edit-user-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const userId = this.dataset.userId;
            const editUserIdInput = getEditUserIdInput();
            const editRolesContainer = getEditRolesContainer();
            const editPermissionsContainer = getEditPermissionsContainer();
            
            if (editUserIdInput) editUserIdInput.value = userId;

            const modal = getEditUserModal();
            if (modal) modal.show();
            
            if (editRolesContainer) {
                editRolesContainer.innerHTML =
                    '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
            }
            if (editPermissionsContainer) {
                editPermissionsContainer.innerHTML =
                    '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
            }

            fetch(`/admin/users/${userId}/edit`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! status: ${response.status}`
                        );
                    }
                    return response.json();
                })
                .then((data) => {
                    const editNameInput = getEditNameInput();
                    const editEmailInput = getEditEmailInput();
                    
                    if (editNameInput) editNameInput.value = data.user.name;
                    if (editEmailInput) editEmailInput.value = data.user.email;

                    const passwordInput = document.getElementById("edit_password");
                    const confirmPasswordInput = document.getElementById("edit_password_confirmation");
                    if (passwordInput) passwordInput.value = "";
                    if (confirmPasswordInput) confirmPasswordInput.value = "";

                    if (editRolesContainer) {
                        editRolesContainer.innerHTML = "";
                        data.roles.forEach((role) => {
                            const isChecked = data.userRoles.includes(role.name)
                                ? "checked"
                                : "";
                            editRolesContainer.innerHTML += `
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="${role.name}" ${isChecked}>
                                <span class="form-check-label">${role.name}</span>
                            </label>
                        `;
                        });
                    }

                    if (editPermissionsContainer) {
                        editPermissionsContainer.innerHTML = "";
                        data.permissions.forEach((permission) => {
                            const isChecked = data.userPermissions.includes(
                                permission.name
                            )
                                ? "checked"
                                : "";
                            editPermissionsContainer.innerHTML += `
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="${permission.name}" ${isChecked}>
                                <span class="form-check-label">${permission.name}</span>
                            </label>
                        `;
                        });
                    }

                    setupRolePermissionSync(
                        editRolesContainer,
                        editPermissionsContainer
                    );

                    syncPermissionsFromRoles(
                        editRolesContainer,
                        editPermissionsContainer
                    );

                    const editUserForm = getEditUserForm();
                    if (editUserForm) editUserForm.action = `/admin/users/${userId}`;
                })
                .catch((error) => {
                    console.error("Error fetching user data:", error);
                    if (editRolesContainer) {
                        editRolesContainer.innerHTML =
                            '<div class="text-danger">Error loading roles</div>';
                    }
                    if (editPermissionsContainer) {
                        editPermissionsContainer.innerHTML =
                            '<div class="text-danger">Error loading permissions</div>';
                    }

                    InventMagApp.showToast(
                            "Error",
                            "Failed to load user data. Please try again.",
                            "error"
                        );
                });
        });
    });
}