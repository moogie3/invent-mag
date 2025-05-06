document
    .getElementById("profileForm")
    .addEventListener("submit", function (event) {
        let newPassword = document.getElementById("new_password").value;

        if (newPassword) {
            event.preventDefault();
            openPasswordModal();
        }
    });

function openPasswordModal() {
    const modal = new bootstrap.Modal(document.getElementById("passwordModal"));
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
        alert("Please enter your current password.");
        return;
    }

    if (newPassword && newPassword !== confirmNewPassword) {
        alert("New password and re-entered password do not match.");
        return;
    }

    document.getElementById("current_password").value = currentPassword;
    document.getElementById("profileForm").submit();
}

function togglePasswordModal() {
    let newPassword = document.getElementById("new_password").value;
    let confirmContainer = document.getElementById("confirmPasswordContainer");
    confirmContainer.style.display = newPassword ? "block" : "none";
}

function showPasswordModal() {
    let newPassword = document.getElementById("new_password").value;
    if (newPassword) {
        let modal = new bootstrap.Modal(
            document.getElementById("passwordModal")
        );
        modal.show();
    } else {
        document.getElementById("profileForm").submit();
    }
}
