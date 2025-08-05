document.addEventListener("DOMContentLoaded", function () {
    // Initialize modals
    const editUserModal = new bootstrap.Modal(
        document.getElementById("editUserModal")
    );
    const deleteUserModal = new bootstrap.Modal(
        document.getElementById("deleteUserModal")
    );
    const createUserModal = new bootstrap.Modal(
        document.getElementById("createUserModal")
    );

    // Edit User Modal Elements
    const editUserForm = document.getElementById("editUserForm");
    const editUserIdInput = document.getElementById("edit_user_id");
    const editNameInput = document.getElementById("edit_name");
    const editEmailInput = document.getElementById("edit_email");
    const editRolesContainer = document.getElementById("edit_roles_container");
    const editPermissionsContainer = document.getElementById(
        "edit_permissions_container"
    );

    // Create User Modal Elements
    const createUserForm = document.querySelector("#createUserModal form");
    const createRolesContainer = document.querySelector(
        '#createUserModal .mb-3:has(input[name="roles[]"])'
    );
    const createPermissionsContainer = document.querySelector(
        '#createUserModal .mb-3:has(input[name="permissions[]"])'
    );

    // Delete User Modal Elements
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    let currentUserId = null;

    // Store role-permission mappings
    let rolePermissionMap = {};
    let allPermissions = [];

    // ======================
    // ROLE-PERMISSION SYNC FUNCTIONALITY
    // ======================

    // Function to fetch role permissions mapping
    function fetchRolePermissions() {
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
                rolePermissionMap = data.rolePermissions;
                allPermissions = data.allPermissions;
                console.log("Role permissions loaded:", rolePermissionMap);
                return data;
            })
            .catch((error) => {
                console.error("Error fetching role permissions:", error);
                // Show user-friendly error message
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

    // Function to sync permissions based on selected roles
    function syncPermissionsFromRoles(rolesContainer, permissionsContainer) {
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

        // Get selected roles
        const selectedRoles = Array.from(roleCheckboxes)
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.value);

        console.log("Selected roles:", selectedRoles);

        // Get permissions from selected roles
        const rolePermissions = new Set();
        selectedRoles.forEach((role) => {
            if (rolePermissionMap[role]) {
                rolePermissionMap[role].forEach((permission) => {
                    rolePermissions.add(permission);
                });
            }
        });

        console.log("Role permissions:", Array.from(rolePermissions));

        // Update permission checkboxes
        permissionCheckboxes.forEach((checkbox) => {
            const permissionName = checkbox.value;
            const label = checkbox.closest("label");

            if (rolePermissions.has(permissionName)) {
                checkbox.checked = true;
                if (label) {
                    label.classList.add("text-muted");
                    label.style.opacity = "0.7";
                }
                checkbox.disabled = true; // Disable permissions that come from roles
                checkbox.title =
                    "This permission is granted by selected role(s)";
            } else {
                // Only uncheck if it was previously disabled (came from role)
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

    // Function to setup role-permission sync for a container
    function setupRolePermissionSync(rolesContainer, permissionsContainer) {
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
                console.log("Role checkbox changed:", this.value, this.checked);
                syncPermissionsFromRoles(rolesContainer, permissionsContainer);
            });
        });

        // Initial sync
        syncPermissionsFromRoles(rolesContainer, permissionsContainer);
    }

    // Function to reset permission states (for modal cleanup)
    function resetPermissionStates(permissionsContainer) {
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

    // ======================
    // CREATE USER MODAL FUNCTIONALITY
    // ======================

    // Setup create user modal role-permission sync
    function setupCreateUserModal() {
        console.log("Setting up create user modal");
        console.log("Create roles container:", createRolesContainer);
        console.log(
            "Create permissions container:",
            createPermissionsContainer
        );

        if (createRolesContainer && createPermissionsContainer) {
            setupRolePermissionSync(
                createRolesContainer,
                createPermissionsContainer
            );

            // Setup modal event listeners
            const createModalElement =
                document.getElementById("createUserModal");

            // Initial sync when modal opens
            createModalElement.addEventListener("shown.bs.modal", function () {
                console.log("Create user modal opened, syncing permissions");
                syncPermissionsFromRoles(
                    createRolesContainer,
                    createPermissionsContainer
                );
            });

            // Reset when modal closes
            createModalElement.addEventListener("hidden.bs.modal", function () {
                console.log("Create user modal closed, resetting states");
                resetPermissionStates(createPermissionsContainer);
            });

            console.log("Create user modal setup complete");
        } else {
            console.error("Create user modal containers not found");
        }
    }

    // ======================
    // EDIT USER FUNCTIONALITY
    // ======================

    // Handle edit button clicks
    document.querySelectorAll(".edit-user-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const userId = this.dataset.userId;
            editUserIdInput.value = userId;

            // Show loading state
            editUserModal.show();
            editRolesContainer.innerHTML =
                '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
            editPermissionsContainer.innerHTML =
                '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';

            // Fetch user data
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
                    console.log("Edit user data:", data); // Debug log

                    // Populate basic fields
                    editNameInput.value = data.user.name;
                    editEmailInput.value = data.user.email;

                    // Clear password fields
                    document.getElementById("edit_password").value = "";
                    document.getElementById(
                        "edit_password_confirmation"
                    ).value = "";

                    // Populate roles
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

                    // Populate permissions
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

                    // Setup role-permission sync for edit modal
                    setupRolePermissionSync(
                        editRolesContainer,
                        editPermissionsContainer
                    );

                    // Initial sync based on current roles
                    syncPermissionsFromRoles(
                        editRolesContainer,
                        editPermissionsContainer
                    );

                    // Set form action
                    editUserForm.action = `/admin/users/${userId}`;
                })
                .catch((error) => {
                    console.error("Error fetching user data:", error);
                    editRolesContainer.innerHTML =
                        '<div class="text-danger">Error loading roles</div>';
                    editPermissionsContainer.innerHTML =
                        '<div class="text-danger">Error loading permissions</div>';

                    // Show error message
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

    // Handle edit form submission
    editUserForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const formData = new FormData(this);
        const userId = editUserIdInput.value;
        const submitBtn = this.querySelector('button[type="submit"]');

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = "Updating...";

        console.log("Submitting form data for user:", userId); // Debug log

        fetch(this.action, {
            method: "POST", // Use POST for PUT/PATCH with _method field
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        })
            .then((response) => {
                console.log("Update response status:", response.status); // Debug log
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Update response data:", data); // Debug log

                if (data.success) {
                    // Show success message
                    if (typeof showToast === "function") {
                        showToast("Success", data.message, "success");
                    } else if (typeof toastr !== "undefined") {
                        toastr.success(data.message);
                    } else {
                        alert(data.message);
                    }

                    // Hide modal
                    editUserModal.hide();

                    // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                    editUserModal._element.addEventListener(
                        "hidden.bs.modal",
                        function handler() {
                            editUserModal._element.removeEventListener(
                                "hidden.bs.modal",
                                handler
                            );
                            // Remove any remaining modal backdrops
                            const backdrops =
                                document.querySelectorAll(".modal-backdrop");
                            backdrops.forEach((backdrop) => backdrop.remove());
                        }
                    );

                    // Dynamically update the user row in the table
                    const userRow = document.querySelector(
                        `tr:has(a[data-user-id="${data.user.id}"])`
                    );
                    if (userRow) {
                        userRow.children[0].textContent = data.user.name; // Update name
                        userRow.children[1].textContent = data.user.email; // Update email

                        // Update roles
                        const rolesCell = userRow.children[2];
                        rolesCell.innerHTML = "";
                        if (data.user.roles && data.user.roles.length > 0) {
                            data.user.roles.forEach((role) => {
                                rolesCell.innerHTML += `<span class="badge bg-blue-lt me-1">${role}</span>`;
                            });
                        }

                        // Update permissions
                        const permissionsCell = userRow.children[3];
                        permissionsCell.innerHTML = "";
                        if (
                            data.user.permissions &&
                            data.user.permissions.length > 0
                        ) {
                            data.user.permissions.forEach((permission) => {
                                permissionsCell.innerHTML += `<span class="badge bg-green-lt me-1">${permission}</span>`;
                            });
                        }
                    }
                } else {
                    // Handle validation errors
                    if (data.errors) {
                        let errorMessage = "Validation errors:\n";
                        Object.keys(data.errors).forEach((key) => {
                            errorMessage += `- ${data.errors[key].join(
                                ", "
                            )}\n`;
                        });

                        if (typeof showToast === "function") {
                            showToast("Error", errorMessage, "error");
                        } else if (typeof toastr !== "undefined") {
                            toastr.error(errorMessage);
                        } else {
                            alert(errorMessage);
                        }
                    } else {
                        if (typeof showToast === "function") {
                            showToast(
                                "Error",
                                data.message || "Failed to update user.",
                                "error"
                            );
                        } else if (typeof toastr !== "undefined") {
                            toastr.error(
                                data.message || "Failed to update user."
                            );
                        } else {
                            alert(data.message || "Failed to update user.");
                        }
                    }
                    console.error(
                        "Error updating user:",
                        data.errors || data.message
                    );
                }
            })
            .catch((error) => {
                console.error("Error updating user:", error);
                if (typeof showToast === "function") {
                    showToast(
                        "Error",
                        "An error occurred while updating the user. Please check the console for details.",
                        "error"
                    );
                } else if (typeof toastr !== "undefined") {
                    toastr.error("An error occurred while updating the user.");
                } else {
                    alert("An error occurred while updating the user.");
                }
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = "Update User";
            });
    });

    // ======================
    // DELETE USER FUNCTIONALITY
    // ======================

    // Handle delete button clicks
    document.querySelectorAll(".delete-user-btn").forEach((button) => {
        button.addEventListener("click", function () {
            currentUserId = this.getAttribute("data-user-id");
            console.log("Delete user ID:", currentUserId); // Debug log

            // Optional: Show user name in the confirmation dialog
            const userRow = this.closest("tr");
            const userName = userRow
                ? userRow.children[0].textContent
                : "this user";
            const modalBody =
                deleteUserModal._element.querySelector(".modal-body p");
            if (modalBody) {
                modalBody.textContent = `Are you sure you want to delete ${userName}?`;
            }
        });
    });

    // Handle confirm delete button click
    confirmDeleteBtn.addEventListener("click", function (e) {
        e.preventDefault();

        if (!currentUserId) {
            console.error("No user ID found");
            return;
        }

        // Show loading state
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = "Deleting...";

        // Create the delete URL
        const deleteUrl = `/admin/users/${currentUserId}`;
        console.log("Delete URL:", deleteUrl); // Debug log

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error("CSRF token not found");
            alert(
                "CSRF token not found. Please refresh the page and try again."
            );
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = "Delete";
            return;
        }

        fetch(deleteUrl, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "Content-Type": "application/json",
            },
        })
            .then((response) => {
                console.log("Delete response status:", response.status); // Debug log
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Delete response data:", data); // Debug log

                if (data.success) {
                    // Hide modal
                    deleteUserModal.hide();

                    // Remove the user row from the table
                    const userRow = document.querySelector(
                        `tr:has(a[data-user-id="${currentUserId}"])`
                    );
                    if (userRow) {
                        // Add fade out animation
                        userRow.style.transition = "opacity 0.3s";
                        userRow.style.opacity = "0";
                        setTimeout(() => {
                            userRow.remove();
                        }, 300);
                    }

                    // Show success message
                    if (typeof showToast === "function") {
                        showToast("Success", data.message, "success");
                    } else if (typeof toastr !== "undefined") {
                        toastr.success(data.message);
                    } else {
                        alert(data.message);
                    }
                } else {
                    // Show error message
                    if (typeof showToast === "function") {
                        showToast(
                            "Error",
                            data.message || "Failed to delete user.",
                            "error"
                        );
                    } else if (typeof toastr !== "undefined") {
                        toastr.error(data.message || "Failed to delete user.");
                    } else {
                        alert(data.message || "Failed to delete user.");
                    }
                }
            })
            .catch((error) => {
                console.error("Error deleting user:", error);
                if (typeof showToast === "function") {
                    showToast(
                        "Error",
                        "An error occurred while deleting the user.",
                        "error"
                    );
                } else if (typeof toastr !== "undefined") {
                    toastr.error("An error occurred while deleting the user.");
                } else {
                    alert("An error occurred while deleting the user.");
                }
            })
            .finally(() => {
                // Reset button state
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = "Delete";
                currentUserId = null;

                // Remove any remaining modal backdrops
                setTimeout(() => {
                    const backdrops =
                        document.querySelectorAll(".modal-backdrop");
                    backdrops.forEach((backdrop) => backdrop.remove());
                }, 500);
            });
    });

    // ======================
    // UTILITY FUNCTIONS
    // ======================

    // Clear form when modal is hidden
    document
        .getElementById("editUserModal")
        .addEventListener("hidden.bs.modal", function () {
            editUserForm.reset();
            editRolesContainer.innerHTML = "";
            editPermissionsContainer.innerHTML = "";
        });

    // Clear delete modal state when hidden
    document
        .getElementById("deleteUserModal")
        .addEventListener("hidden.bs.modal", function () {
            currentUserId = null;
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = "Delete";
        });

    // ======================
    // INITIALIZATION
    // ======================

    // Initialize everything when the page loads
    fetchRolePermissions()
        .then(() => {
            console.log(
                "Role permissions fetched successfully, setting up modals"
            );
            setupCreateUserModal();
        })
        .catch((error) => {
            console.error("Failed to fetch role permissions:", error);
            // Still try to setup the modal even if role permissions failed
            setupCreateUserModal();
        });
});
