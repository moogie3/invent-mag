let _editUserModal = null;
let _deleteUserModal = null;
let _createUserModal = null;

export function getEditUserModal() {
    const el = document.getElementById("editUserModal");
    if (!el) return null;
    if (!_editUserModal) {
        _editUserModal = new bootstrap.Modal(el, {});
    }
    return _editUserModal;
}

export function getDeleteUserModal() {
    const el = document.getElementById("deleteUserModal");
    if (!el) return null;
    if (!_deleteUserModal) {
        _deleteUserModal = new bootstrap.Modal(el, {});
    }
    return _deleteUserModal;
}

export function getCreateUserModal() {
    const el = document.getElementById("createUserModal");
    if (!el) return null;
    if (!_createUserModal) {
        _createUserModal = new bootstrap.Modal(el, {});
    }
    return _createUserModal;
}

export function getEditUserForm() {
    return document.getElementById("editUserForm");
}

export function getEditUserIdInput() {
    return document.getElementById("edit_user_id");
}

export function getEditNameInput() {
    return document.getElementById("edit_name");
}

export function getEditEmailInput() {
    return document.getElementById("edit_email");
}

export function getEditRolesContainer() {
    return document.getElementById("edit_roles_container");
}

export function getEditPermissionsContainer() {
    return document.getElementById("edit_permissions_container");
}

export function getCreateUserForm() {
    const modal = document.getElementById("createUserModal");
    return modal ? modal.querySelector('form') : null;
}

export function getCreateRolesContainer() {
    const modal = document.getElementById("createUserModal");
    return modal ? modal.querySelector('.mb-3:has(input[name="roles[]"])') : null;
}

export function getCreatePermissionsContainer() {
    const modal = document.getElementById("createUserModal");
    return modal ? modal.querySelector('.mb-3:has(input[name="permissions[]"])') : null;
}

export function getConfirmDeleteBtn() {
    return document.getElementById("confirmDeleteBtn");
}

export function resetModalInstances() {
    _editUserModal = null;
    _deleteUserModal = null;
    _createUserModal = null;
}

// Backward compatibility - deprecated, use getter functions instead
export const editUserModal = null;
export const deleteUserModal = null;
export const createUserModal = null;
export const editUserForm = null;
export const editUserIdInput = null;
export const editNameInput = null;
export const editEmailInput = null;
export const editRolesContainer = null;
export const editPermissionsContainer = null;
export const createUserForm = null;
export const createRolesContainer = null;
export const createPermissionsContainer = null;
export const confirmDeleteBtn = null;
