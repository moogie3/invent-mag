export function openPasswordModal() {
    const modal = new bootstrap.Modal(
        document.getElementById("passwordModal")
    );
    modal.show();
}

export function submitProfileForm() {
    let currentPassword = document.getElementById(
        "modal_current_password"
    ).value;
    let newPassword = document.getElementById("new_password").value;
    let confirmNewPassword = document.getElementById(
        "confirm_new_password"
    ).value;

    if (!currentPassword) {
        InventMagApp.showToast(
            "Warning",
            "Please enter your current password.",
            "warning"
        );
        return;
    }

    if (newPassword && newPassword !== confirmNewPassword) {
        InventMagApp.showToast(
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

export function togglePasswordModal() {
    let newPassword = document.getElementById("new_password").value;
    let confirmContainer = document.getElementById(
        "confirmPasswordContainer"
    );
    if (confirmContainer) {
        confirmContainer.style.display = newPassword ? "block" : "none";
    }
}

export function showPasswordModal() {
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

window.submitProfileForm = submitProfileForm;
window.togglePasswordModal = togglePasswordModal;
window.showPasswordModal = showPasswordModal;
window.openPasswordModal = openPasswordModal;