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
        showToast("Warning", "Please enter your current password.", "warning");
        return;
    }

    if (newPassword && newPassword !== confirmNewPassword) {
        showToast("Warning", "New password and re-entered password do not match.", "warning");
        return;
    }

    const profileForm = document.getElementById("profileForm");
    const formData = new FormData(profileForm);

    // Append current password from modal to form data
    formData.append('current_password', currentPassword);

    fetch(profileForm.action, {
        method: profileForm.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Success', data.message, 'success');
            const passwordModal = bootstrap.Modal.getInstance(document.getElementById("passwordModal"));
            if (passwordModal) {
                passwordModal.hide();
            }
            // Clear password fields after successful update
            document.getElementById("modal_current_password").value = '';
            document.getElementById("new_password").value = '';
            document.getElementById("confirm_new_password").value = '';
        } else {
            showToast('Error', data.message || 'Failed to update profile.', 'error');
            console.error('Profile update error:', data.errors);
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        showToast('Error', 'An error occurred while updating profile. Please check the console for details.', 'error');
    });
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

// Toast notification functions (copied from user.js)
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
