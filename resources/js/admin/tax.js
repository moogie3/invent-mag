document.addEventListener('DOMContentLoaded', function () {
    window.shortcutManager.register('ctrl+s', () => {
        const form = document.getElementById('taxSettingsForm');
        if (form) {
            form.requestSubmit();
        }
    }, 'Save Tax Settings');
});
