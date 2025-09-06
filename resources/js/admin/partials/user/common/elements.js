export const editUserModal = new bootstrap.Modal(
    document.getElementById("editUserModal")
);
export const deleteUserModal = new bootstrap.Modal(
    document.getElementById("deleteUserModal")
);
export const createUserModal = new bootstrap.Modal(
    document.getElementById("createUserModal")
);

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
