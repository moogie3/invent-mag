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

        // Apply theme immediately
        window.applyTheme(newTheme);

        // Update icons immediately
        updateThemeIcons(newTheme);

        // Send to backend to save preference
        fetch("/admin/settings/update-theme-mode", {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ theme_mode: newTheme }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Theme mode updated on backend:", data);
            })
            .catch((error) => {
                console.error("Error updating theme mode:", error);
                // Revert theme if backend update fails
                window.applyTheme(currentTheme);
                updateThemeIcons(currentTheme);
            });
    }

    function updateToggleVisibility() {
        const showToggle =
            body.getAttribute("data-show-theme-toggle") === "true";
        if (navbarToggleContainer) {
            navbarToggleContainer.style.display = showToggle ? "block" : "none";
        }
        if (sidebarToggleContainer) {
            sidebarToggleContainer.style.display = showToggle
                ? "block"
                : "none";
        }
    }

    // Initialize theme icons based on current theme
    const currentTheme = body.getAttribute("data-bs-theme") || "light";
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
