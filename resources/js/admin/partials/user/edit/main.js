import { editUserModal, editUserIdInput, editNameInput, editEmailInput, editRolesContainer, editPermissionsContainer } from '../common/elements.js';
import { syncPermissionsFromRoles, setupRolePermissionSync } from '../rolesPermissions/sync.js';

export function initEditUserModal() {
    document.querySelectorAll(".edit-user-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const userId = this.dataset.userId;
            editUserIdInput.value = userId;

            editUserModal.show();
            editRolesContainer.innerHTML =
                '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
            editPermissionsContainer.innerHTML =
                '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';

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
                    editNameInput.value = data.user.name;
                    editEmailInput.value = data.user.email;

                    document.getElementById("edit_password").value = "";
                    document.getElementById(
                        "edit_password_confirmation"
                    ).value = "";

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

                    setupRolePermissionSync(
                        editRolesContainer,
                        editPermissionsContainer
                    );

                    syncPermissionsFromRoles(
                        editRolesContainer,
                        editPermissionsContainer
                    );

                    editUserForm.action = `/admin/users/${userId}`;
                })
                .catch((error) => {
                    console.error("Error fetching user data:", error);
                    editRolesContainer.innerHTML =
                        '<div class="text-danger">Error loading roles</div>';
                    editPermissionsContainer.innerHTML =
                        '<div class="text-danger">Error loading permissions</div>';

                    if (typeof showToast === "function") {
                        showToast(
                            "Error",
                            "Failed to load user data. Please try again.",
                            "error"
                        );
                    } else if (typeof toastr !== "undefined") {
                        toastr.error(
                            "Failed to load user data. Please try again."
                        );
                    } else {
                        alert("Failed to load user data. Please try again.");
                    }
                });
        });
    });
}
