document.addEventListener("DOMContentLoaded", function () {
    const htmlElement = document.documentElement;
    const navbarToggleContainer = document.getElementById(
        "theme-toggle-navbar-container"
    );
    const sidebarToggleContainer = document.getElementById(
        "theme-toggle-sidebar-container"
    );
    const themeToggleButton = document.getElementById("theme-toggle-button");
    const themeIcon = document.getElementById("theme-icon");

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
                navbarLightIcon.style.display = "inline"; // Show sun icon in dark mode
                navbarDarkIcon.style.display = "none";
            } else {
                navbarLightIcon.style.display = "none";
                navbarDarkIcon.style.display = "inline"; // Show moon icon in light mode
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
                sidebarLightIcon.style.display = "inline"; // Show sun icon in dark mode
                sidebarDarkIcon.style.display = "none";
            } else {
                sidebarLightIcon.style.display = "none";
                sidebarDarkIcon.style.display = "inline"; // Show moon icon in light mode
            }
        }

        // Update login page icon if it exists
        if (themeIcon) {
            if (theme === "dark") {
                themeIcon.classList.remove("ti-moon");
                themeIcon.classList.add("ti-sun");
            } else {
                themeIcon.classList.remove("ti-sun");
                themeIcon.classList.add("ti-moon");
            }
        }
    }

    function toggleTheme() {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcons(newTheme);

        if (!csrfTokenMeta) {
            return; // Not logged in, no need to sync with server
        }

        // Send an AJAX request to update the user's system settings
        fetch('/admin/settings/update-theme-mode', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfTokenMeta.getAttribute('content')
            },
            body: JSON.stringify({
                theme_mode: newTheme,
            })
        })
        .then(response => {
            if (!response.ok) {
                console.error('Failed to save theme setting:', response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Theme setting saved successfully.');
                const event = new CustomEvent('themeModeUpdated', {
                    detail: { themeMode: newTheme }
                });
                document.dispatchEvent(event);
            } else {
                console.error('Error saving theme setting:', data.message);
            }
        })
        .catch(error => {
            console.error('Error sending AJAX request for theme setting:', error);
        });
    }

    // Initialize theme icons based on the current theme
    const initialTheme = htmlElement.getAttribute('data-bs-theme');
    updateThemeIcons(initialTheme);

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

    if (themeToggleButton) {
        themeToggleButton.addEventListener("click", function (e) {
            e.preventDefault();
            toggleTheme();
        });
    }
});
