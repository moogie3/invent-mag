// Wrap everything in DOMContentLoaded to ensure DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    // Profile form submit handler
    const profileForm = document.getElementById("profileForm");
    if (profileForm) {
        profileForm.addEventListener("submit", function (event) {
            let newPassword = document.getElementById("new_password").value;

            if (newPassword) {
                event.preventDefault();
                openPasswordModal();
            }
        });
    }

    // Password modal functions
    function openPasswordModal() {
        const modal = new bootstrap.Modal(
            document.getElementById("passwordModal")
        );
        modal.show();
    }

    function submitProfileForm() {
        let currentPassword = document.getElementById(
            "modal_current_password"
        ).value;
        let newPassword = document.getElementById("new_password").value;
        let confirmNewPassword = document.getElementById(
            "confirm_new_password"
        ).value;

        if (!currentPassword) {
            showToast(
                "Warning",
                "Please enter your current password.",
                "warning"
            );
            return;
        }

        if (newPassword && newPassword !== confirmNewPassword) {
            showToast(
                "Warning",
                "New password and re-entered password do not match.",
                "warning"
            );
            return;
        }

        const profileForm = document.getElementById("profileForm");
        const formData = new FormData(profileForm);

        // Append current password from modal to form data
        formData.append("current_password", currentPassword);

        fetch(profileForm.action, {
            method: profileForm.method,
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast("Success", data.message, "success");
                    const passwordModal = bootstrap.Modal.getInstance(
                        document.getElementById("passwordModal")
                    );
                    if (passwordModal) {
                        passwordModal.hide();
                        // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                        passwordModal._element.addEventListener(
                            "hidden.bs.modal",
                            function handler() {
                                passwordModal._element.removeEventListener(
                                    "hidden.bs.modal",
                                    handler
                                ); // Remove the listener
                                // Explicitly remove any remaining modal backdrops
                                const backdrops =
                                    document.querySelectorAll(
                                        ".modal-backdrop"
                                    );
                                backdrops.forEach((backdrop) =>
                                    backdrop.remove()
                                );
                            }
                        );
                    }
                    // Clear password fields after successful update
                    document.getElementById("modal_current_password").value =
                        "";
                    document.getElementById("new_password").value = "";
                    document.getElementById("confirm_new_password").value = "";
                } else {
                    showToast(
                        "Error",
                        data.message || "Failed to update profile.",
                        "error"
                    );
                    console.error("Profile update error:", data.errors);
                }
            })
            .catch((error) => {
                console.error("Error updating profile:", error);
                showToast(
                    "Error",
                    "An error occurred while updating profile. Please check the console for details.",
                    "error"
                );
            });
    }

    function togglePasswordModal() {
        let newPassword = document.getElementById("new_password").value;
        let confirmContainer = document.getElementById(
            "confirmPasswordContainer"
        );
        if (confirmContainer) {
            confirmContainer.style.display = newPassword ? "block" : "none";
        }
    }

    function showPasswordModal() {
        let newPassword = document.getElementById("new_password").value;
        if (newPassword) {
            let modal = new bootstrap.Modal(
                document.getElementById("passwordModal")
            );
            modal.show();
        } else {
            const profileForm = document.getElementById("profileForm");
            if (profileForm) {
                profileForm.submit();
            }
        }
    }

    // Make functions globally available if needed by other scripts
    window.submitProfileForm = submitProfileForm;
    window.togglePasswordModal = togglePasswordModal;
    window.showPasswordModal = showPasswordModal;
    window.openPasswordModal = openPasswordModal;

    
});
