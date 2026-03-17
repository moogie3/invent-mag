let _editUserModal = null;
let _deleteUserModal = null;
let _createUserModal = null;

export function getEditUserModal() {
    if (!_editUserModal) {
        const el = document.getElementById("editUserModal");
        _editUserModal = el ? new bootstrap.Modal(el, {}) : null;
    }
    return _editUserModal;
}

export function getDeleteUserModal() {
    if (!_deleteUserModal) {
        const el = document.getElementById("deleteUserModal");
        _deleteUserModal = el ? new bootstrap.Modal(el, {}) : null;
    }
    return _deleteUserModal;
}

export function getCreateUserModal() {
    if (!_createUserModal) {
        const el = document.getElementById("createUserModal");
        _createUserModal = el ? new bootstrap.Modal(el, {}) : null;
    }
    return _createUserModal;
}

// Backward compatibility - these will be null if elements don't exist
export const editUserModal = document.getElementById("editUserModal") ? new bootstrap.Modal(document.getElementById("editUserModal", {})) : null;
export const deleteUserModal = document.getElementById("deleteUserModal") ? new bootstrap.Modal(document.getElementById("deleteUserModal", {})) : null;
export const createUserModal = document.getElementById("createUserModal") ? new bootstrap.Modal(document.getElementById("createUserModal", {})) : null;

export const editUserForm = document.getElementById("editUserForm");
export const editUserIdInput = document.getElementById("edit_user_id");
export const editNameInput = document.getElementById("edit_name");
export const editEmailInput = document.getElementById("edit_email");
export const editRolesContainer = document.getElementById("edit_roles_container");
export const editPermissionsContainer = document.getElementById(
    "edit_permissions_container"
);

export const createUserForm = document.querySelector("#createUserModal form");
export const createRolesContainer = document.querySelector(
    '#createUserModal .mb-3:has(input[name="roles[]"])'
);
export const createPermissionsContainer = document.querySelector(
    '#createUserModal .mb-3:has(input[name="permissions[]"])'
);

export const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
