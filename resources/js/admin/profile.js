document.addEventListener("DOMContentLoaded", function () {

    window.shortcutManager.register('ctrl+s', () => {
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.requestSubmit();
        }
    }, 'Save Profile');
});