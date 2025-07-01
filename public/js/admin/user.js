document.addEventListener('DOMContentLoaded', function () {
    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const editUserForm = document.getElementById('editUserForm');
    const editUserIdInput = document.getElementById('edit_user_id');
    const editNameInput = document.getElementById('edit_name');
    const editEmailInput = document.getElementById('edit_email');
    const editRolesContainer = document.getElementById('edit_roles_container');
    const editPermissionsContainer = document.getElementById('edit_permissions_container');

    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.dataset.userId;
            editUserIdInput.value = userId;

            // Fetch user data
            fetch(`/admin/users/${userId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    editNameInput.value = data.user.name;
                    editEmailInput.value = data.user.email;

                    // Populate roles
                    editRolesContainer.innerHTML = '';
                    data.roles.forEach(role => {
                        const isChecked = data.userRoles.includes(role.name) ? 'checked' : '';
                        editRolesContainer.innerHTML += `
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="${role.name}" ${isChecked}>
                                <span class="form-check-label">${role.name}</span>
                            </label>
                        `;
                    });

                    // Populate permissions
                    editPermissionsContainer.innerHTML = '';
                    data.permissions.forEach(permission => {
                        const isChecked = data.userPermissions.includes(permission.name) ? 'checked' : '';
                        editPermissionsContainer.innerHTML += `
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="${permission.name}" ${isChecked}>
                                <span class="form-check-label">${permission.name}</span>
                            </label>
                        `;
                    });

                    editUserForm.action = `/admin/users/${userId}`;
                    editUserModal.show();
                })
                .catch(error => console.error('Error fetching user data:', error));
        });
    });

    editUserForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(this);
        const userId = editUserIdInput.value;

        fetch(this.action, {
            method: 'POST', // Use POST for PUT/PATCH with _method field
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(response => response.json())
        .then(data => {
            console.log('Update response data:', data); // Log the response data
            if (data.success) {
                showToast('Success', data.message, 'success');
                editUserModal.hide();

                // Dynamically update the user row in the table
                const userRow = document.querySelector(`tr:has(a[data-user-id="${data.user.id}"])`);
                if (userRow) {
                    userRow.children[0].textContent = data.user.name; // Update name
                    userRow.children[1].textContent = data.user.email; // Update email

                    // Update roles
                    const rolesCell = userRow.children[2];
                    rolesCell.innerHTML = '';
                    data.user.roles.forEach(role => {
                        rolesCell.innerHTML += `<span class="badge bg-blue-lt">${role}</span>`;
                    });

                    // Update permissions
                    const permissionsCell = userRow.children[3];
                    permissionsCell.innerHTML = '';
                    data.user.permissions.forEach(permission => {
                        permissionsCell.innerHTML += `<span class="badge bg-green-lt">${permission}</span>`;
                    });
                }
            } else {
                showToast('Error', data.message || 'Failed to update user.', 'error');
                console.error('Error updating user:', data.errors);
            }
        })
        .catch(error => {
            console.error('Error updating user:', error); // Log any fetch errors
            showToast('Error', 'An error occurred while updating the user. Please check the console for details.', 'error');
        });
    });
});

// Add a log to confirm the script is loaded
console.log('user.js script loaded.');

// Toast notification functions (copied from purchase-order.js)
function showToast(title, message, type = "info", duration = 4000) {
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        toastContainer.className =
            "toast-container position-fixed bottom-0 end-0 p-3";
        toastContainer.style.zIndex = "1050";
        document.body.appendChild(toastContainer);

        if (!document.getElementById("toast-styles")) {
            const style = document.createElement("style");
            style.id = "toast-styles";
            style.textContent = `
                    .toast-enter {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    .toast-show {
                        transform: translateX(0);
                        opacity: 1;
                        transition: transform 0.3s ease, opacity 0.3s ease;
                    }
                    .toast-exit {
                        transform: translateX(100%);
                        opacity: 0;
                        transition: transform 0.3s ease, opacity 0.3s ease;
                    }
                `;
            document.head.appendChild(style);
        }
    }

    const toast = document.createElement("div");
    toast.className =
        "toast toast-enter align-items-center text-white bg-" +
        getToastColor(type) +
        " border-0";
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");

    toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

    toastContainer.appendChild(toast);

    void toast.offsetWidth;

    toast.classList.add("toast-show");

    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration,
    });
    bsToast.show();

    const closeButton = toast.querySelector(".btn-close");
    closeButton.addEventListener("click", () => {
        hideToast(toast);
    });

    const hideTimeout = setTimeout(() => {
        hideToast(toast);
    }, duration);

    toast._hideTimeout = hideTimeout;
}

function hideToast(toast) {
    if (toast._hideTimeout) {
        clearTimeout(toast._hideTimeout);
    }

    toast.classList.remove("toast-show");
    toast.classList.add("toast-exit");

    setTimeout(() => {
        toast.remove();
    }, 300);
}

function getToastColor(type) {
    switch (type) {
        case "success":
            return "success";
        case "error":
            return "danger";
        case "warning":
            return "warning";
        default:
            return "info";
    }
}
