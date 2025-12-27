import { rolePermissionMap } from '../common/state.js';

export function syncPermissionsFromRoles(rolesContainer, permissionsContainer) {
    if (!rolesContainer || !permissionsContainer) {
        console.error("Role or permission container not found");
        return;
    }

    const roleCheckboxes = rolesContainer.querySelectorAll(
        'input[name="roles[]"]'
    );
    const permissionCheckboxes = permissionsContainer.querySelectorAll(
        'input[name="permissions[]"]'
    );

    const selectedRoles = Array.from(roleCheckboxes)
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const rolePermissions = new Set();
    selectedRoles.forEach((role) => {
        if (rolePermissionMap[role]) {
            rolePermissionMap[role].forEach((permission) => {
                rolePermissions.add(permission);
            });
        }
    });

    permissionCheckboxes.forEach((checkbox) => {
        const permissionName = checkbox.value;
        const label = checkbox.closest("label");

        if (rolePermissions.has(permissionName)) {
            checkbox.checked = true;
            if (label) {
                label.classList.add("text-muted");
                label.style.opacity = "0.7";
            }
            checkbox.disabled = true;
            checkbox.title =
                "This permission is granted by selected role(s)";
        } else {
            if (checkbox.disabled) {
                checkbox.checked = false;
            }
            if (label) {
                label.classList.remove("text-muted");
                label.style.opacity = "1";
            }
            checkbox.disabled = false;
            checkbox.title = "";
        }
    });
}

export function setupRolePermissionSync(rolesContainer, permissionsContainer) {
    if (!rolesContainer || !permissionsContainer) {
        console.error(
            "Cannot setup role-permission sync: containers not found"
        );
        return;
    }

    const roleCheckboxes = rolesContainer.querySelectorAll(
        'input[name="roles[]"]'
    );

    roleCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            syncPermissionsFromRoles(rolesContainer, permissionsContainer);
        });
    });

    syncPermissionsFromRoles(rolesContainer, permissionsContainer);
}

export function resetPermissionStates(permissionsContainer) {
    if (!permissionsContainer) return;

    const permissionCheckboxes = permissionsContainer.querySelectorAll(
        'input[name="permissions[]"]'
    );

    permissionCheckboxes.forEach((checkbox) => {
        checkbox.disabled = false;
        checkbox.title = "";
        const label = checkbox.closest("label");
        if (label) {
            label.classList.remove("text-muted");
            label.style.opacity = "1";
        }
    });
}
