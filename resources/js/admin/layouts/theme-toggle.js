document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;
    const navbarToggleContainer = document.getElementById(
        "theme-toggle-navbar-container"
    );
    const sidebarToggleContainer = document.getElementById(
        "theme-toggle-sidebar-container"
    );

    function updateThemeIcons(theme) {
        // Update navbar icons
        const navbarLightIcon = document.querySelector(
            "#theme-toggle-navbar .theme-icon-light"
        );
        const navbarDarkIcon = document.querySelector(
            "#theme-toggle-navbar .theme-icon-dark"
        );

        if (navbarLightIcon && navbarDarkIcon) {
            if (theme === "dark") {
                navbarLightIcon.style.display = "none";
                navbarDarkIcon.style.display = "inline";
            } else {
                navbarLightIcon.style.display = "inline";
                navbarDarkIcon.style.display = "none";
            }
        }

        // Update sidebar icons if they exist
        const sidebarLightIcon = document.querySelector(
            "#theme-toggle-sidebar .theme-icon-light"
        );
        const sidebarDarkIcon = document.querySelector(
            "#theme-toggle-sidebar .theme-icon-dark"
        );

        if (sidebarLightIcon && sidebarDarkIcon) {
            if (theme === "dark") {
                sidebarLightIcon.style.display = "none";
                sidebarDarkIcon.style.display = "inline";
            } else {
                sidebarLightIcon.style.display = "inline";
                sidebarDarkIcon.style.display = "none";
            }
        }
    }

    function toggleTheme() {
        const currentTheme = body.getAttribute("data-bs-theme");
        const newTheme = currentTheme === "dark" ? "light" : "dark";

        console.log("Current theme:", currentTheme, "New theme:", newTheme);

        // Update the theme mode select and body attribute for preview
        const themeModeSelect = document.getElementById('themeModeSelect');
        if (themeModeSelect) {
            themeModeSelect.value = newTheme;
        }
        body.setAttribute("data-bs-theme", newTheme);

        // Update icons immediately for preview
        updateThemeIcons(newTheme);
    }

    function updateToggleVisibility() {
        const showThemeToggleCheckbox = document.getElementById('showThemeToggleCheckbox');
        const showToggle = showThemeToggleCheckbox ? showThemeToggleCheckbox.checked : true; // Default to true if checkbox not found

        if (navbarToggleContainer) {
            navbarToggleContainer.style.display = showToggle ? "block" : "none";
        }
        if (sidebarToggleContainer) {
            sidebarToggleContainer.style.display = showToggle
                ? "block"
                : "none";
        }
    }

    // Initialize theme icons based on current theme mode select value
    const themeModeSelect = document.getElementById('themeModeSelect');
    const currentTheme = themeModeSelect ? themeModeSelect.value : "light"; // Default to light if select not found
    updateThemeIcons(currentTheme);
    updateToggleVisibility();

    // Add event listeners to the toggle buttons
    if (navbarToggleContainer) {
        navbarToggleContainer.addEventListener("click", function (e) {
            e.preventDefault();
            toggleTheme();
        });
    }
    if (sidebarToggleContainer) {
        sidebarToggleContainer.addEventListener("click", function (e) {
            e.preventDefault();
            toggleTheme();
        });
    }

    // Also add event listeners to any theme toggle buttons inside the containers
    const navbarToggleBtn = document.querySelector("#theme-toggle-navbar");
    const sidebarToggleBtn = document.querySelector("#theme-toggle-sidebar");

    if (navbarToggleBtn) {
        navbarToggleBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleTheme();
        });
    }
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleTheme();
        });
    }
});
