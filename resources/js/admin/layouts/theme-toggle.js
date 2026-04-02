import { onPageLoad } from '../utils/onPageLoad.js';

onPageLoad(() => {
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

        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcons(newTheme);

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
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

    // Always update icons to match current theme on every page load
    const currentTheme = htmlElement.getAttribute('data-bs-theme') || 'light';
    updateThemeIcons(currentTheme);

    const handleThemeClick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleTheme();
    };

    // Attach click handlers - remove old ones first
    if (navbarToggleContainer) {
        const newNavbarContainer = navbarToggleContainer.cloneNode(true);
        navbarToggleContainer.parentNode.replaceChild(newNavbarContainer, navbarToggleContainer);
        newNavbarContainer.addEventListener("click", handleThemeClick);
    }
    if (sidebarToggleContainer) {
        const newSidebarContainer = sidebarToggleContainer.cloneNode(true);
        sidebarToggleContainer.parentNode.replaceChild(newSidebarContainer, sidebarToggleContainer);
        newSidebarContainer.addEventListener("click", handleThemeClick);
    }

    const navbarToggleBtn = document.querySelector("#theme-toggle-navbar");
    const sidebarToggleBtn = document.querySelector("#theme-toggle-sidebar");

    if (navbarToggleBtn) {
        const newNavbarBtn = navbarToggleBtn.cloneNode(true);
        navbarToggleBtn.parentNode.replaceChild(newNavbarBtn, navbarToggleBtn);
        newNavbarBtn.addEventListener("click", handleThemeClick);
    }
    if (sidebarToggleBtn) {
        const newSidebarBtn = sidebarToggleBtn.cloneNode(true);
        sidebarToggleBtn.parentNode.replaceChild(newSidebarBtn, sidebarToggleBtn);
        newSidebarBtn.addEventListener("click", handleThemeClick);
    }

    if (themeToggleButton) {
        const newThemeToggleBtn = themeToggleButton.cloneNode(true);
        themeToggleButton.parentNode.replaceChild(newThemeToggleBtn, themeToggleButton);
        newThemeToggleBtn.addEventListener("click", handleThemeClick);
    }

    const authThemeToggle = document.querySelector("#theme-toggle");
    if (authThemeToggle) {
        authThemeToggle.replaceWith(authThemeToggle.cloneNode(true));
        const newAuthThemeToggle = document.querySelector("#theme-toggle");
        if (newAuthThemeToggle) {
            newAuthThemeToggle.addEventListener("click", handleThemeClick);
        }
    }
});
