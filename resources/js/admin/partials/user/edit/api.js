import { getEditUserModal, getEditUserForm, getEditUserIdInput } from '../common/elements.js';

export function initEditUserFormSubmission() {
    const editUserForm = getEditUserForm();
    if (!editUserForm) return;
    
    if (editUserForm.dataset.listenerAttached) return;
    editUserForm.dataset.listenerAttached = 'true';
    
    editUserForm.addEventListener("submit", function (event) {
        if (event.defaultPrevented) return;
        event.preventDefault();

        const formData = new FormData(this);
        const editUserIdInput = getEditUserIdInput();
        const userId = editUserIdInput ? editUserIdInput.value : '';
        const submitBtn = this.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.textContent = "Updating...";

        fetch(this.action, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        })
            .then((response) => {
                return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
            })
            .then(({ ok, status, data }) => {
                if (data.success) {
                    InventMagApp.showToast("Success", data.message, "success");

                    const modal = getEditUserModal();
                    if (modal) {
                        modal.hide();
                        modal._element.addEventListener(
                            "hidden.bs.modal",
                            function handler() {
                                modal._element.removeEventListener(
                                    "hidden.bs.modal",
                                    handler
                                );
                                document.querySelectorAll(".modal-backdrop").forEach(b => b.remove());
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                            }
                        );
                    }

                    const userRow = document.querySelector(
                        `tr:has(a[data-user-id="${data.user.id}"])`
                    );
                    if (userRow) {
                        userRow.children[0].textContent = data.user.name;
                        userRow.children[1].textContent = data.user.email;

                        const rolesCell = userRow.children[2];
                        rolesCell.innerHTML = "";
                        if (data.user.roles && data.user.roles.length > 0) {
                            data.user.roles.forEach((role) => {
                                rolesCell.innerHTML += `<span class="badge bg-blue-lt me-1">${role}</span>`;
                            });
                        }

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
                    if (data.errors) {
                        let errorMessage = "";
                        Object.keys(data.errors).forEach((key) => {
                            errorMessage += `- ${data.errors[key].join(", ")}\n`;
                        });
                        InventMagApp.showToast("Warning", errorMessage, "warning");
                    } else {
                        InventMagApp.showToast("Warning", data.message || "Failed to update user.", "warning");
                    }
                }
            })
            .catch((error) => {
                console.error("Error updating user:", error);
                InventMagApp.showToast("Error", "An error occurred while updating the user.", "error");
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = "Update User";
            });
    });
}