import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/settings/tax")) return;
    window.shortcutManager.register('ctrl+s', () => {
        const form = document.getElementById('taxSettingsForm');
        if (form) {
            form.requestSubmit();
        }
    }, 'Save Tax Settings');
});
