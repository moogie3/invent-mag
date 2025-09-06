export let currentUserId = null;
export let rolePermissionMap = {};
export let allPermissions = [];

export function setCurrentUserId(id) {
    currentUserId = id;
}

export function setRolePermissionMap(map) {
    rolePermissionMap = map;
}

export function setAllPermissions(permissions) {
    allPermissions = permissions;
}
