import { onPageLoad } from '../utils/onPageLoad.js';

onPageLoad(() => {
    // Remove any previously registered delegation listener to prevent stacking across Turbo navigations
    if (window._themeToggleClickHandler) {
        document.removeEventListener('click', window._themeToggleClickHandler);
    }
    const htmlElement = document.documentElement;

    function updateThemeIcons(theme) {
        document.querySelectorAll('.theme-icon-light').forEach(el => {
            el.style.display = theme === 'dark' ? 'inline' : 'none';
        });
        document.querySelectorAll('.theme-icon-dark').forEach(el => {
            el.style.display = theme === 'dark' ? 'none' : 'inline';
        });

        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.classList.remove('ti-moon');
                themeIcon.classList.add('ti-sun');
            } else {
                themeIcon.classList.remove('ti-sun');
                themeIcon.classList.add('ti-moon');
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

        const isSuperAdmin = window.location.hostname.startsWith('super.');
        const themeUrl = isSuperAdmin
            ? '/settings/update-theme-mode'
            : '/admin/settings/update-theme-mode';
        const themeMethod = isSuperAdmin ? 'POST' : 'PUT';

        fetch(themeUrl, {
            method: themeMethod,
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

    const handleThemeClick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleTheme();
    };

    // Attach click listeners using event delegation — no cloning needed
    window._themeToggleClickHandler = function (e) {
        const toggleSidebar = e.target.closest('#theme-toggle-sidebar, #theme-toggle-sidebar-container');
        const toggleNavbar = e.target.closest('#theme-toggle-navbar, #theme-toggle-navbar-container');
        const toggleBtn = e.target.closest('#theme-toggle-button');
        const toggleAuth = e.target.closest('#theme-toggle');

        if (toggleSidebar || toggleNavbar || toggleBtn || toggleAuth) {
            handleThemeClick(e);
        }
    };
    document.addEventListener('click', window._themeToggleClickHandler);

    // Set correct icons on load
    const currentTheme = htmlElement.getAttribute('data-bs-theme') || 'light';
    updateThemeIcons(currentTheme);
});
