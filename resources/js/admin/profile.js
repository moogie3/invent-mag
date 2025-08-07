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
        document.getElementById("current_password").value = currentPassword;
        profileForm.submit();
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
