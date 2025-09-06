import { togglePasswordVisibility } from './partials/auth/togglePasswordVisibility.js';
import { showContentAfterLoading } from './partials/auth/showContentAfterLoading.js';
import { themeToggle } from './partials/auth/themeToggle.js';

document.addEventListener("DOMContentLoaded", function () {
    togglePasswordVisibility();
    showContentAfterLoading();
    themeToggle();
});
