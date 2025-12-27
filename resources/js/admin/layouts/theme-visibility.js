
document.addEventListener('DOMContentLoaded', function () {
    // Find all theme toggle containers
    const navbarToggleContainer = document.getElementById('theme-toggle-navbar-container');
    const sidebarToggleContainer = document.getElementById('theme-toggle-sidebar-container');
    const themeToggleContainers = [navbarToggleContainer, sidebarToggleContainer].filter(el => el !== null);

    // Find elements on the settings page
    const visibilityCheckbox = document.getElementById('showThemeToggleCheckbox');
    const themeModeSelect = document.getElementById('themeModeSelect');

    // Function to update the visibility of the theme toggle icons
    function updateIconVisibility() {
        // Use window.userSettings for the source of truth
        const shouldShow = window.userSettings ? (window.userSettings.show_theme_toggle ?? true) : true;
        const displayValue = shouldShow ? 'block' : 'none';

        themeToggleContainers.forEach(container => {
            container.style.display = displayValue;
        });
    }

    // Function to update the state of the settings page elements
    function updateSettingsPage() {
        if (!visibilityCheckbox || !themeModeSelect) {
            return; // We are not on the settings page
        }

        // Use window.userSettings for the source of truth
        const shouldShow = window.userSettings ? (window.userSettings.show_theme_toggle ?? true) : true;

        // 1. Set the checkbox state
        visibilityCheckbox.checked = shouldShow;

        // 2. Make the theme mode dropdown readonly if theme toggle is visible
        if (shouldShow) {
            themeModeSelect.classList.add('readonly-select');
        } else {
            themeModeSelect.classList.remove('readonly-select');
        }
    }

    // --- Event Listeners ---

    // Listen for changes on the visibility checkbox on the settings page
    if (visibilityCheckbox) {
        visibilityCheckbox.addEventListener('change', function () {
            // The actual saving to the backend is handled by the form submission
            // on the settings page. This just updates the local state immediately.
            if (window.userSettings) {
                window.userSettings.show_theme_toggle = visibilityCheckbox.checked;
            }
            updateIconVisibility();
            updateSettingsPage();
        });
    }

    // Listen for when user settings are loaded (from settings.js)
    document.addEventListener('usersettingsloaded', () => {
        updateIconVisibility();
        updateSettingsPage();
    });

    // --- Initial Execution ---

    // Run the functions on page load to set the correct initial state
    // This will use the default values until window.userSettings is loaded
    updateIconVisibility();
    updateSettingsPage();
});
