class ShortcutManager {
    constructor() {
        if (window.shortcutManager) {
            return window.shortcutManager;
        }

        this.shortcuts = [];
        this.descriptions = {
            showHelpModal: "Showing help modal",
            focusSearch: "Focus global search input",
            closeModal: "Close active modal/popup",
        };
        this.isMac = navigator.platform.toUpperCase().indexOf("MAC") >= 0;

        // Use a bound function so we can remove it if needed,
        // though usually we want this to stay globally.
        this.boundHandleKeydown = this.handleKeydown.bind(this);
        document.addEventListener("keydown", this.boundHandleKeydown);

        window.shortcutManager = this;
    }

    // Route name of the current page, injected server-side on <body data-route>.
    currentRoute() {
        return (document.body && document.body.dataset.route) || "";
    }

    // A shortcut fires only when it is global or its route-prefix scope
    // matches the current page ("admin.product" covers admin.product.create).
    scopeMatches(shortcut, route) {
        if (shortcut.scope === "global") return true;
        if (!shortcut.scope || !route) return false;
        return route === shortcut.scope || route.startsWith(shortcut.scope + ".");
    }

    // scope: "global" = every page; "admin.x" = that route subtree;
    // omitted = snapshot of the route active right now, so a page script
    // registering during its own page load is automatically page-scoped.
    register(keys, callback, description, scope = null) {
        const resolvedScope = scope === null ? this.currentRoute() : scope;

        // Overwrite same keys within the same scope to prevent accumulation
        this.unregister(keys, resolvedScope);

        this.shortcuts.push({
            keys: keys.toLowerCase(),
            callback,
            description,
            scope: resolvedScope,
        });
    }

    // Omitted scope = remove the key everywhere (legacy callers expect
    // "kill this key" semantics); explicit scope = replace within scope only.
    unregister(keys, scope) {
        this.shortcuts = this.shortcuts.filter((shortcut) => {
            if (shortcut.keys.toLowerCase() !== keys.toLowerCase()) return true;
            return scope !== undefined && shortcut.scope !== scope;
        });
    }

    clearPageShortcuts() {
        // Keep global shortcuts, clear others
        const globalShortcuts = ["/", "shift+?", "escape"];
        this.shortcuts = this.shortcuts.filter((shortcut) =>
            globalShortcuts.includes(shortcut.keys.toLowerCase())
        );
    }

    handleKeydown(event) {
        if (window.userSettings && !window.userSettings.enable_keyboard_shortcuts) {
            return;
        }

        if (!event.key) return;

        if (!event.key || !event.target) return;

        const key = event.key.toLowerCase();
        const ctrl = this.isMac ? event.metaKey : event.ctrlKey;
        const alt = event.altKey;
        const shift = event.shiftKey;

        // Special case for Esc key to always work
        if (key === "escape") {
            const openModal = document.querySelector(".modal.show");
            if (openModal) {
                const modalInstance = bootstrap.Modal.getInstance(openModal);
                if (modalInstance && typeof modalInstance.hide === 'function') {
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

        const route = this.currentRoute();

        for (const shortcut of this.shortcuts) {
            if (!this.scopeMatches(shortcut, route)) continue;

            const shortcutParts = shortcut.keys.split("+").map((k) => k.trim());
            const mainKey = shortcutParts.pop();
            const requiredModifiers = shortcutParts;

            if (
                key === mainKey &&
                requiredModifiers.length === pressedModifiers.length &&
                requiredModifiers.every((mod) => pressedModifiers.includes(mod))
            ) {
                event.preventDefault();
                if (shortcut.description) {
                    const descriptionText =
                        this.descriptions[shortcut.description] ||
                        shortcut.description;
                    window.InventMagApp.showToast(
                        "Shortcut",
                        descriptionText,
                        "info"
                    );
                }
                shortcut.callback(event);
                return; // Stop processing other shortcuts
            }
        }
    }

    getShortcuts() {
        return this.shortcuts;
    }

    showShortcutsModal() {
        const modalElement = document.getElementById("keyboardShortcutsModal");
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement, {});
            const tbody = document.getElementById("keyboardShortcutsList");
            tbody.innerHTML = ""; // Clear previous content

            const route = this.currentRoute();
            const uniqueShortcuts = Array.from(
                new Map(
                    this.getShortcuts()
                        .filter((s) => this.scopeMatches(s, route))
                        .map((s) => [s.keys, s])
                ).values()
            );

            const translations = tbody.dataset;
            uniqueShortcuts.forEach((shortcut) => {
                const row = tbody.insertRow();
                const keyCell = row.insertCell();
                const actionCell = row.insertCell();
                keyCell.innerHTML = `<strong>${shortcut.keys
                    .split("+")
                    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(" + ")}</strong>`;
                actionCell.textContent =
                    translations[shortcut.description] || shortcut.description;
            });
            modal.show();
        }
    }
}

window.shortcutManager = new ShortcutManager();

// Global shortcuts
window.shortcutManager.register(
    "/",
    () => {
        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
            searchInput.focus();
        }
    },
    "focusSearch",
    "global"
);

window.shortcutManager.register(
    "shift+?",
    () => {
        window.shortcutManager.showShortcutsModal();
    },
    "showHelpModal",
    "global"
);

window.shortcutManager.register(
    "escape",
    () => {
        const openModal = document.querySelector(".modal.show");
        if (openModal) {
            const modalInstance = bootstrap.Modal.getInstance(openModal);
            if (modalInstance && typeof modalInstance.hide === 'function') {
                modalInstance.hide();
            }
        }
    },
    "closeModal",
    "global"
);
