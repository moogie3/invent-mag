import { togglePasswordVisibility } from './partials/auth/togglePasswordVisibility.js';
import { showContentAfterLoading } from './partials/auth/showContentAfterLoading.js';

document.addEventListener("DOMContentLoaded", function () {
    togglePasswordVisibility();
    showContentAfterLoading();
    window.handleSessionNotifications();
});
