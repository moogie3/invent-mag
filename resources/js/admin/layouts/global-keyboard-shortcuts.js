const shortcuts = [
    { keys: 'Ctrl + S', action: (window.translations && window.translations.keyboard_shortcuts_save_form) || 'Save current form' },
    { keys: 'Cmd + S', action: (window.translations && window.translations.keyboard_shortcuts_save_form) || 'Save current form (Mac)' },
    { keys: 'Shift + ?', action: (window.translations && window.translations.keyboard_shortcuts_show_help_modal) || 'Show this help modal' },
    { keys: '/', action: (window.translations && window.translations.keyboard_shortcuts_focus_search_input) || 'Focus global search input' },
    { keys: 'Esc', action: (window.translations && window.translations.keyboard_shortcuts_close_modal) || 'Close active modal/popup' },
    { keys: 'Ctrl + Z', action: (window.translations && window.translations.keyboard_shortcuts_undo_action) || 'Undo last action' },
    { keys: 'Cmd + Z', action: (window.translations && window.translations.keyboard_shortcuts_undo_action) || 'Undo last action (Mac)' },
    { keys: 'Ctrl + Y', action: (window.translations && window.translations.keyboard_shortcuts_redo_action) || 'Redo last action' },
    { keys: 'Cmd + Shift + Z', action: (window.translations && window.translations.keyboard_shortcuts_redo_action) || 'Redo last action (Mac)' },
    { keys: 'Alt + N', action: (window.translations && window.translations.keyboard_shortcuts_create_new_item) || 'Create new item (contextual)' },
    { keys: 'Alt + E', action: (window.translations && window.translations.keyboard_shortcuts_edit_item) || 'Edit selected item (contextual)' },
    { keys: 'Alt + D', action: (window.translations && window.translations.keyboard_shortcuts_delete_item) || 'Delete selected item (contextual)' },
];

let activeKeyboardShortcutHandler = null;

const handleKeyboardShortcut = (event) => {
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;

    // Ctrl+S / Cmd+S - Save
    if ((isMac ? event.metaKey : event.ctrlKey) && event.key === 's') {
        console.log('Ctrl+S pressed');
        event.preventDefault();
        const customEvent = new CustomEvent('ctrl-s-pressed');
        document.dispatchEvent(customEvent);
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
                keyCell.innerHTML = `<strong>${shortcut.keys}</strong>`;
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

export function enableKeyboardShortcuts() {
    if (!activeKeyboardShortcutHandler) {
        document.addEventListener('keydown', handleKeyboardShortcut);
        activeKeyboardShortcutHandler = handleKeyboardShortcut;
        console.log('Keyboard shortcuts enabled globally.');
    }
}

export function disableKeyboardShortcuts() {
    if (activeKeyboardShortcutHandler) {
        document.removeEventListener('keydown', activeKeyboardShortcutHandler);
        activeKeyboardShortcutHandler = null;
        console.log('Keyboard shortcuts disabled globally.');
    }
}

export function initShowShortcutsModalButton() {
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
}