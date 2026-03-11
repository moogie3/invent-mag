import { togglePasswordVisibility } from './partials/auth/togglePasswordVisibility.js';
import { showContentAfterLoading } from './partials/auth/showContentAfterLoading.js';

document.addEventListener("DOMContentLoaded", function () {
    togglePasswordVisibility();
    showContentAfterLoading();

    const loginForm = document.getElementById('loginSubmitBtn');
    if (loginForm) {
        loginForm.addEventListener('click', function() {
            const btnText = this.querySelector('.login-btn-text');
            const btnLoading = this.querySelector('.login-btn-loading');
            if (btnText && btnLoading) {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                this.disabled = true;
            }
        });
    }
});
