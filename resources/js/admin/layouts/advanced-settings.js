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

        const shortcuts = [
            { keys: 'Ctrl + S', action: 'Save current form' },
            { keys: 'Cmd + S', action: 'Save current form (Mac)' },
            { keys: 'Shift + ?', action: 'Show this help modal' },
            { keys: '/', action: 'Focus global search input' },
            { keys: 'Esc', action: 'Close active modal/popup' },
            { keys: 'Ctrl + Z', action: 'Undo last action' },
            { keys: 'Cmd + Z', action: 'Undo last action (Mac)' },
            { keys: 'Ctrl + Y', action: 'Redo last action' },
            { keys: 'Cmd + Shift + Z', action: 'Redo last action (Mac)' },
            { keys: 'Alt + N', action: 'Create new item (contextual)' },
            { keys: 'Alt + E', action: 'Edit selected item (contextual)' },
            { keys: 'Alt + D', action: 'Delete selected item (contextual)' },
        ];

        const handleKeyboardShortcut = (event) => {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;

            // Ctrl+S / Cmd+S - Save
            if ((isMac ? event.metaKey : event.ctrlKey) && event.key === 's') {
                event.preventDefault();
                const form = document.getElementById('systemSettingsForm'); // Assuming this is the main form
                if (form) {
                    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    InventMagApp.showToast('Info', 'Attempting to save...', 'info');
                } else {
                    InventMagApp.showToast('Info', 'No active form to save.', 'info');
                }
            }
            // Shift + ? - Show Shortcuts Modal
            else if (event.shiftKey && event.key === '?') {
                event.preventDefault();
                const modalElement = document.getElementById('keyboardShortcutsModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    const tbody = document.getElementById('keyboardShortcutsList');
                    tbody.innerHTML = ''; // Clear previous content

                    shortcuts.forEach(shortcut => {
                        const row = tbody.insertRow();
                        const keyCell = row.insertCell();
                        const actionCell = row.insertCell();
                        keyCell.textContent = shortcut.keys;
                        actionCell.textContent = shortcut.action;
                    });
                    modal.show();
                }
            }
            // / - Focus Search
            else if (event.key === '/') {
                event.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                    InventMagApp.showToast('Info', 'Focusing search input.', 'info');
                } else {
                    InventMagApp.showToast('Info', 'No global search input found.', 'info');
                }
            }
            // Esc - Close Modal
            else if (event.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modalInstance = bootstrap.Modal.getInstance(openModal);
                    if (modalInstance) {
                        modalInstance.hide();
                        InventMagApp.showToast('Info', 'Closing modal.', 'info');
                    }
                }
            }
            // Ctrl+Z / Cmd+Z - Undo
            else if ((isMac ? event.metaKey : event.ctrlKey) && event.key === 'z') {
                event.preventDefault();
                InventMagApp.showToast('Info', 'Undo action triggered.', 'info');
                // TODO: Implement actual undo logic
            }
            // Ctrl+Y / Cmd+Shift+Z - Redo
            else if ((isMac ? (event.metaKey && event.shiftKey) : event.ctrlKey) && event.key === 'Z') { // Note: event.key is 'Z' for Shift+z
                event.preventDefault();
                InventMagApp.showToast('Info', 'Redo action triggered.', 'info');
                // TODO: Implement actual redo logic
            }
            // Alt+N - New Item
            else if (event.altKey && event.key === 'n') {
                event.preventDefault();
                InventMagApp.showToast('Info', 'New item action triggered.', 'info');
                // TODO: Implement new item logic
            }
            // Alt+E - Edit Item
            else if (event.altKey && event.key === 'e') {
                event.preventDefault();
                InventMagApp.showToast('Info', 'Edit item action triggered.', 'info');
                // TODO: Implement edit item logic
            }
            // Alt+D - Delete Item
            else if (event.altKey && event.key === 'd') {
                event.preventDefault();
                InventMagApp.showToast('Info', 'Delete item action triggered.', 'info');
                // TODO: Implement delete item logic
            }
        };

        document.addEventListener('keydown', handleKeyboardShortcut);
        // Store the handler so it can be removed if settings change
        window.activeKeyboardShortcutHandler = handleKeyboardShortcut;

        // Add event listener for the new button
        const showShortcutsModalBtn = document.getElementById('showShortcutsModalBtn');
        if (showShortcutsModalBtn) {
            showShortcutsModalBtn.addEventListener('click', () => {
                const modalElement = document.getElementById('keyboardShortcutsModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    const tbody = document.getElementById('keyboardShortcutsList');
                    tbody.innerHTML = ''; // Clear previous content

                    shortcuts.forEach(shortcut => {
                        const row = tbody.insertRow();
                        const keyCell = row.insertCell();
                        const actionCell = row.insertCell();
                        keyCell.textContent = shortcut.keys;
                        actionCell.textContent = shortcut.action;
                    });
                    modal.show();
                }
            });
        }

    } else {
        console.log('Keyboard shortcuts are disabled.');
        // Remove the event listener if it was previously active
        if (window.activeKeyboardShortcutHandler) {
            document.removeEventListener('keydown', window.activeKeyboardShortcutHandler);
            delete window.activeKeyboardShortcutHandler;
        }
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