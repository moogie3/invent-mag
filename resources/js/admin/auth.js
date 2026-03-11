import { togglePasswordVisibility } from './partials/auth/togglePasswordVisibility.js';
import { showContentAfterLoading } from './partials/auth/showContentAfterLoading.js';

document.addEventListener("DOMContentLoaded", function () {
    togglePasswordVisibility();
    showContentAfterLoading();

    const loginBtn = document.getElementById('loginSubmitBtn');
    const loginForm = loginBtn ? loginBtn.closest('form') : null;

    // Reset button state on page load (in case of validation errors from previous submit)
    if (loginBtn) {
        const btnText = loginBtn.querySelector('.login-btn-text');
        const btnLoading = loginBtn.querySelector('.login-btn-loading');
        if (btnText && btnLoading) {
            btnText.classList.remove('d-none');
            btnLoading.classList.add('d-none');
            loginBtn.disabled = false;
            loginBtn.style.pointerEvents = 'auto';
        }
    }

    // Handle form submit - show loading state
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            if (loginBtn) {
                const btnText = loginBtn.querySelector('.login-btn-text');
                const btnLoading = loginBtn.querySelector('.login-btn-loading');
                if (btnText && btnLoading) {
                    btnText.classList.add('d-none');
                    btnLoading.classList.remove('d-none');
                    loginBtn.disabled = true;
                    loginBtn.style.pointerEvents = 'none';
                }
            }
        });
    }
});
