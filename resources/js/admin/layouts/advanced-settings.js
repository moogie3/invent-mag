/**
 * Advanced Settings - Turbo-compatible version
 */
import { onPageLoad } from '../utils/onPageLoad.js';

function applyAdvancedSettings(settings) {
    // Enable Debug Mode
    // Console logging placeholder for debug mode

    // Enable Keyboard Shortcuts
    // shortcutManager is always active, handled in global-keyboard-shortcuts.js

    // Show Tooltips
    if (settings.show_tooltips) {
        document.body.classList.remove('no-tooltips');
    } else {
        document.body.classList.add('no-tooltips');
    }

    // Compact Mode - Removed
}

function initAdvancedSettings() {
    if (window.advancedSettingsInitialized) return;
    window.advancedSettingsInitialized = true;

    if (window.userSettings) {
        applyAdvancedSettings(window.userSettings);
    } else {
        document.addEventListener('usersettingsloaded', () => {
            applyAdvancedSettings(window.userSettings);
        }, { once: true });
    }
}

onPageLoad(() => {
    initAdvancedSettings();
});

document.addEventListener('turbo:before-cache', () => {
    window.advancedSettingsInitialized = false;
});
