
document.addEventListener('DOMContentLoaded', function () {
    const storageKey = 'invent-mag-show-theme-toggle';

    // Find all theme toggle containers
    const navbarToggleContainer = document.getElementById('theme-toggle-navbar-container');
    const sidebarToggleContainer = document.getElementById('theme-toggle-sidebar-container');
    const themeToggleContainers = [navbarToggleContainer, sidebarToggleContainer].filter(el => el !== null);

    // Find elements on the settings page
    const visibilityCheckbox = document.getElementById('showThemeToggleCheckbox');
    const themeModeSelect = document.getElementById('themeModeSelect');

    // Function to update the visibility of the theme toggle icons
    function updateIconVisibility() {
        // Default to true if the setting isn't in localStorage yet
        const shouldShow = localStorage.getItem(storageKey) === 'true' || localStorage.getItem(storageKey) === null;
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

        // Default to true if the setting isn't in localStorage yet
        const shouldShow = localStorage.getItem(storageKey) === 'true' || localStorage.getItem(storageKey) === null;

        // 1. Set the checkbox state
        visibilityCheckbox.checked = shouldShow;

        // 2. Enable/disable the theme mode dropdown
        themeModeSelect.disabled = shouldShow;
    }

    // --- Event Listeners ---

    // Listen for clicks on the visibility checkbox on the settings page
    if (visibilityCheckbox) {
        visibilityCheckbox.addEventListener('change', function () {
            const isChecked = visibilityCheckbox.checked;
            // Save the preference to localStorage
            localStorage.setItem(storageKey, isChecked);

            // Immediately update the UI based on the new preference
            updateIconVisibility();
            updateSettingsPage();
        });
    }

    // --- Initial Execution ---

    // Run the functions on page load to set the correct initial state
    updateIconVisibility();
    updateSettingsPage();
});
