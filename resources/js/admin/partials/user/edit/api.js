import { editUserModal, editUserIdInput, editUserForm } from '../common/elements.js';

export function initEditUserFormSubmission() {
    editUserForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const formData = new FormData(this);
        const userId = editUserIdInput.value;
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
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    if (typeof showToast === "function") {
                        showToast("Success", data.message, "success");
                    } else if (typeof toastr !== "undefined") {
                        toastr.success(data.message);
                    } else {
                        alert(data.message);
                    }

                    editUserModal.hide();

                    editUserModal._element.addEventListener(
                        "hidden.bs.modal",
                        function handler() {
                            editUserModal._element.removeEventListener(
                                "hidden.bs.modal",
                                handler
                            );
                            const backdrops =
                                document.querySelectorAll(".modal-backdrop");
                            backdrops.forEach((backdrop) => backdrop.remove());
                        }
                    );

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
                submitBtn.disabled = false;
                submitBtn.textContent = "Update User";
            });
    });
}
