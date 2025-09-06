import { setRolePermissionMap, setAllPermissions } from '../common/state.js';

export function fetchRolePermissions() {
    return fetch("/admin/roles-permissions", {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
            "X-CSRF-TOKEN":
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            setRolePermissionMap(data.rolePermissions);
            setAllPermissions(data.allPermissions);
            return data;
        })
        .catch((error) => {
            console.error("Error fetching role permissions:", error);
            if (typeof showToast === "function") {
                showToast(
                    "Error",
                    "Failed to load role permissions. Some features may not work correctly.",
                    "error"
                );
            } else if (typeof toastr !== "undefined") {
                toastr.error(
                    "Failed to load role permissions. Some features may not work correctly."
                );
            }
            throw error;
        });
}
