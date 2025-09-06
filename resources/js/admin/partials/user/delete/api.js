import { confirmDeleteBtn, deleteUserModal } from '../common/elements.js';
import { currentUserId, setCurrentUserId } from '../common/state.js';

export function initDeleteUserFormSubmission() {
    confirmDeleteBtn.addEventListener("click", function (e) {
        e.preventDefault();

        if (!currentUserId) {
            console.error("No user ID found");
            return;
        }

        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = "Deleting...";

        const deleteUrl = `/admin/users/${currentUserId}`;

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
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    deleteUserModal.hide();

                    const userRow = document.querySelector(
                        `tr:has(a[data-user-id="${currentUserId}"])`
                    );
                    if (userRow) {
                        userRow.style.transition = "opacity 0.3s";
                        userRow.style.opacity = "0";
                        setTimeout(() => {
                            userRow.remove();
                        }, 300);
                    }

                    if (typeof showToast === "function") {
                        showToast("Success", data.message, "success");
                    } else if (typeof toastr !== "undefined") {
                        toastr.success(data.message);
                    } else {
                        alert(data.message);
                    }
                } else {
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
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = "Delete";
                setCurrentUserId(null);

                setTimeout(() => {
                    const backdrops =
                        document.querySelectorAll(".modal-backdrop");
                    backdrops.forEach((backdrop) => backdrop.remove());
                }, 500);
            });
    });
}
