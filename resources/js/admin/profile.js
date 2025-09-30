import { initProfileForm } from './partials/profile/form.js';

document.addEventListener("DOMContentLoaded", function () {
    initProfileForm();

    window.shortcutManager.register('ctrl+s', () => {
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.requestSubmit();
        }
    }, 'Save Profile');
});