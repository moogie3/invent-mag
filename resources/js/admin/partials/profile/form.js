import { openPasswordModal } from './modal.js';

export function initProfileForm() {
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
}
