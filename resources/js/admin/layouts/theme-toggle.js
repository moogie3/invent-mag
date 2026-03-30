import { onPageLoad } from '../utils/onPageLoad.js';

onPageLoad(() => {
    // Only initialize if not already done for this path
    if (window.themeToggleInitialized && window.themeToggleLastPath === window.location.pathname) {
        return;
    }

    window.themeToggleLastPath = window.location.pathname;
    window.themeToggleInitialized = true;

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
        const navbarLightIcon = document.querySelector(
            "#theme-toggle-navbar .theme-icon-light"
        );
        const navbarDarkIcon = document.querySelector(
            "#theme-toggle-navbar .theme-icon-dark"
        );

        if (navbarLightIcon && navbarDarkIcon) {
            if (theme === "dark") {
                navbarLightIcon.style.display = "inline";
                navbarDarkIcon.style.display = "none";
            } else {
                navbarLightIcon.style.display = "none";
                navbarDarkIcon.style.display = "inline";
            }
        }

        const sidebarLightIcon = document.querySelector(
            "#theme-toggle-sidebar .theme-icon-light"
        );
        const sidebarDarkIcon = document.querySelector(
            "#theme-toggle-sidebar .theme-icon-dark"
        );

        if (sidebarLightIcon && sidebarDarkIcon) {
            if (theme === "dark") {
                sidebarLightIcon.style.display = "inline";
                sidebarDarkIcon.style.display = "none";
            } else {
                sidebarLightIcon.style.display = "none";
                sidebarDarkIcon.style.display = "inline";
            }
        }

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
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcons(newTheme);

        if (!csrfTokenMeta) {
            return;
        }

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
                return response.text().then(text => {
                    console.error('Failed to save theme setting:', response.statusText, text);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                const event = new CustomEvent('themeModeUpdated', {
                    detail: { themeMode: newTheme }
                });
                document.dispatchEvent(event);
            }
        })
        .catch(error => {
            console.error('Error sending AJAX request for theme setting:', error);
        });
    }

    const initialTheme = htmlElement.getAttribute('data-bs-theme');
    updateThemeIcons(initialTheme);

    const handleThemeClick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleTheme();
    };

    // Remove any existing listeners first to prevent duplicates
    if (navbarToggleContainer) {
        navbarToggleContainer.replaceWith(navbarToggleContainer.cloneNode(true));
        const newNavbarContainer = document.getElementById("theme-toggle-navbar-container");
        if (newNavbarContainer) {
            newNavbarContainer.addEventListener("click", handleThemeClick);
        }
    }
    if (sidebarToggleContainer) {
        sidebarToggleContainer.replaceWith(sidebarToggleContainer.cloneNode(true));
        const newSidebarContainer = document.getElementById("theme-toggle-sidebar-container");
        if (newSidebarContainer) {
            newSidebarContainer.addEventListener("click", handleThemeClick);
        }
    }

    const navbarToggleBtn = document.querySelector("#theme-toggle-navbar");
    const sidebarToggleBtn = document.querySelector("#theme-toggle-sidebar");

    if (navbarToggleBtn) {
        navbarToggleBtn.replaceWith(navbarToggleBtn.cloneNode(true));
        const newNavbarBtn = document.querySelector("#theme-toggle-navbar");
        if (newNavbarBtn) {
            newNavbarBtn.addEventListener("click", handleThemeClick);
        }
    }
    if (sidebarToggleBtn) {
        sidebarToggleBtn.replaceWith(sidebarToggleBtn.cloneNode(true));
        const newSidebarBtn = document.querySelector("#theme-toggle-sidebar");
        if (newSidebarBtn) {
            newSidebarBtn.addEventListener("click", handleThemeClick);
        }
    }

    if (themeToggleButton) {
        themeToggleButton.addEventListener("click", handleThemeClick);
    }
});

// Reset the initialized flag before Turbo caches so the theme toggle
// re-initializes properly on the next page visit
document.addEventListener('turbo:before-cache', () => {
    window.themeToggleInitialized = false;
});
