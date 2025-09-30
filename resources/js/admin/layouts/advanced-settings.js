/**
 * @file This file contains the logic for the "Advanced Settings" section of the settings page.
 * It is loaded on all pages to apply the advanced settings globally.
 */

/**
 * Applies the advanced user settings to the application.
 * @param {object} settings - The user settings object.
 */
function applyAdvancedSettings(settings) {
    // 1. Enable Debug Mode (Placeholder)
    if (settings.enable_debug_mode) {
        // TODO: Implement the desired debug mode functionality.
        // This could involve logging specific data to the console,
        // displaying a debug overlay, or other debugging tools.
        console.log('Debug mode is enabled. No specific functionality has been implemented yet.');
    }

    // 2. Enable Keyboard Shortcuts
    if (settings.enable_keyboard_shortcuts) {
        console.log('Keyboard shortcuts are enabled.');
        // The shortcutManager is always active, and the help modal is triggered by shift+? directly.
        // No explicit initialization needed here.
    } else {
        console.log('Keyboard shortcuts are disabled.');
    }

    // 3. Show Tooltips
    if (settings.show_tooltips) {
        // This assumes you are using Bootstrap's tooltip component.
        // If you are using a different library, this code will need to be adjusted.
        document.body.classList.remove('no-tooltips');
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } else {
        document.body.classList.add('no-tooltips');
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (tooltip) {
                tooltip.dispose();
            }
        });
    }

    // 4. Compact Mode
    if (settings.compact_mode) {
        // This adds a 'compact-mode' class to the body.
        // You will need to add CSS rules to define what "compact mode" looks like.
        document.body.classList.add('compact-mode');
    } else {
        document.body.classList.remove('compact-mode');
    }
}

/**
 * Initializes the advanced settings functionality.
 */
async function initAdvancedSettings() {
    if (window.userSettings) {
        applyAdvancedSettings(window.userSettings);
    } else {
        document.addEventListener('usersettingsloaded', () => {
            applyAdvancedSettings(window.userSettings);
        });
    }
}

initAdvancedSettings();