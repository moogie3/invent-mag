class ShortcutManager {
    constructor() {
        this.shortcuts = [];
        this.isMac = navigator.platform.toUpperCase().indexOf("MAC") >= 0;
        document.addEventListener("keydown", this.handleKeydown.bind(this));
    }

    register(keys, callback, description) {
        this.shortcuts.push({
            keys: keys.toLowerCase(),
            callback,
            description,
        });
    }

    unregister(keys) {
        this.shortcuts = this.shortcuts.filter(
            (shortcut) => shortcut.keys.toLowerCase() !== keys.toLowerCase()
        );
    }

    handleKeydown(event) {
        const key = event.key.toLowerCase();
        const ctrl = this.isMac ? event.metaKey : event.ctrlKey;
        const alt = event.altKey;
        const shift = event.shiftKey;

        // Special case for Esc key to always work
        if (key === "escape") {
            const openModal = document.querySelector(".modal.show");
            if (openModal) {
                const modalInstance = bootstrap.Modal.getInstance(openModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            return;
        }

        // Ignore shortcuts when typing in inputs, textareas, or content-editable elements
        if (
            event.target.isContentEditable ||
            ["INPUT", "TEXTAREA", "SELECT"].includes(event.target.tagName)
        ) {
            // Allow some shortcuts like Ctrl+S to work in forms
            if (
                !(
                    (this.isMac ? event.metaKey : event.ctrlKey) &&
                    event.key.toLowerCase() === "s"
                )
            ) {
                return;
            }
        }

        const pressedModifiers = [];
        if (ctrl) pressedModifiers.push("ctrl");
        if (alt) pressedModifiers.push("alt");
        if (shift) pressedModifiers.push("shift");

        for (const shortcut of this.shortcuts) {
            const shortcutParts = shortcut.keys.split("+").map((k) => k.trim());
            const mainKey = shortcutParts.pop();
            const requiredModifiers = shortcutParts;

            if (
                key === mainKey &&
                requiredModifiers.length === pressedModifiers.length &&
                requiredModifiers.every((mod) => pressedModifiers.includes(mod))
            ) {
                event.preventDefault();
                shortcut.callback(event);
                return; // Stop processing other shortcuts
            }
        }
    }

    getShortcuts() {
        return this.shortcuts;
    }
}

window.shortcutManager = new ShortcutManager();

// Global shortcuts
window.shortcutManager.register(
    "shift+?",
    () => {
        const modalElement = document.getElementById("keyboardShortcutsModal");
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            const tbody = document.getElementById("keyboardShortcutsList");
            tbody.innerHTML = ""; // Clear previous content

            const uniqueShortcuts = Array.from(
                new Map(
                    window.shortcutManager
                        .getShortcuts()
                        .map((s) => [s.keys, s])
                ).values()
            );

            uniqueShortcuts.forEach((shortcut) => {
                const row = tbody.insertRow();
                const keyCell = row.insertCell();
                const actionCell = row.insertCell();
                keyCell.innerHTML = `<strong>${shortcut.keys
                    .split("+")
                    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(" + ")}</strong>`;
                actionCell.textContent = shortcut.description;
            });
            modal.show();
        }
    },
    "Show this help modal"
);

window.shortcutManager.register(
    "/",
    () => {
        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
            searchInput.focus();
        }
    },
    "Focus global search input"
);

window.shortcutManager.register(
    "escape",
    () => {
        const openModal = document.querySelector(".modal.show");
        if (openModal) {
            const modalInstance = bootstrap.Modal.getInstance(openModal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    },
    "Close active modal/popup"
);
