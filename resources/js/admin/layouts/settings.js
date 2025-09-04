function resetToDefaults() {
    if (
        confirm(
            "Are you sure you want to reset all settings to default values? This action cannot be undone."
        )
    ) {
        // Reset form to default values
        document.getElementById("systemSettingsForm").reset();

        // Interface Layout Settings
        document.querySelector('select[name="navigation_type"]').value =
            "sidebar";

        // Theme Configuration
        document.querySelector('select[name="theme_mode"]').value = "light";

        // Notifications & Alerts
        document.querySelector('select[name="notification_duration"]').value =
            "5";

        // Session & Security
        document.querySelector('select[name="auto_logout_time"]').value = "60";

        // Performance
        document.querySelector('select[name="data_refresh_rate"]').value = "30";

        // Language & Localization
        document.querySelector('select[name="system_language"]').value = "en";

        // Set checkboxes to default values
        // Interface Layout
        document.querySelector('input[name="sidebar_lock"]').checked = false;
        document.querySelector('input[name="sticky_navbar"]').checked = false;

        // Theme Configuration
        document.querySelector(
            'input[name="show_theme_toggle"]'
        ).checked = true;

        // Notifications & Alerts
        document.querySelector(
            'input[name="enable_sound_notifications"]'
        ).checked = true;
        document.querySelector(
            'input[name="enable_browser_notifications"]'
        ).checked = true;
        document.querySelector(
            'input[name="show_success_messages"]'
        ).checked = true;

        // Session & Security
        document.querySelector(
            'input[name="remember_last_page"]'
        ).checked = true;

        // Performance
        document.querySelector(
            'input[name="enable_animations"]'
        ).checked = true;
        document.querySelector('input[name="lazy_load_images"]').checked = true;

        // Advanced Settings
        document.querySelector(
            'input[name="enable_debug_mode"]'
        ).checked = false;
        document.querySelector(
            'input[name="enable_keyboard_shortcuts"]'
        ).checked = true;
        document.querySelector('input[name="show_tooltips"]').checked = true;
        document.querySelector('input[name="compact_mode"]').checked = false;
    }
}

// Apply settings preview and real-time updates
document.addEventListener("DOMContentLoaded", function () {
    const themeSelect = document.querySelector('select[name="theme_mode"]');
    const navigationSelect = document.querySelector(
        'select[name="navigation_type"]'
    );
    const animationToggle = document.querySelector(
        'input[name="enable_animations"]'
    );
    const compactModeToggle = document.querySelector(
        'input[name="compact_mode"]'
    );
    const debugToggle = document.querySelector(
        'input[name="enable_debug_mode"]'
    );

    // Theme preview functionality - Apply immediately
    if (themeSelect) {
        themeSelect.addEventListener("change", function () {
            const selectedTheme = this.value;
            console.log("Theme changed to:", selectedTheme);

            // Apply theme immediately for preview
            if (window.applyTheme) {
                // window.applyTheme(selectedTheme);
                document.body.setAttribute("data-bs-theme", selectedTheme);
            }
        });
    }

    const showThemeToggleCheckbox = document.getElementById('showThemeToggleCheckbox');
    const themeModeSelect = document.getElementById('themeModeSelect');
    const navbarToggleContainer = document.getElementById("theme-toggle-navbar-container");
    const sidebarToggleContainer = document.getElementById("theme-toggle-sidebar-container");

    function updateThemeToggleState() {
        console.log('updateThemeToggleState called.');
        if (!showThemeToggleCheckbox || !themeModeSelect) {
            console.log('Missing checkbox or themeModeSelect.');
            return;
        }

        console.log('showThemeToggleCheckbox.checked:', showThemeToggleCheckbox.checked);
        console.log('navbarToggleContainer:', navbarToggleContainer);
        console.log('sidebarToggleContainer:', sidebarToggleContainer);

        if (showThemeToggleCheckbox.checked) {
            
            if (navbarToggleContainer) {
                navbarToggleContainer.style.display = "block";
                console.log('Navbar toggle set to block.');
            }
            if (sidebarToggleContainer) {
                sidebarToggleContainer.style.display = "block";
                console.log('Sidebar toggle set to block.');
            }
        } else {
            
            if (navbarToggleContainer) {
                navbarToggleContainer.style.display = "none";
                console.log('Navbar toggle set to none.');
            }
            if (sidebarToggleContainer) {
                sidebarToggleContainer.style.display = "none";
                console.log('Sidebar toggle set to none.');
            }
        }
    }

    // Removed client-side immediate update for theme toggle visibility
    // if (showThemeToggleCheckbox) {
    //     showThemeToggleCheckbox.addEventListener('change', updateThemeToggleState);
    //     updateThemeToggleState(); // Set initial state
    // }

    

    // Animation preview
    if (animationToggle) {
        animationToggle.addEventListener("change", function () {
            const enableAnimations = this.checked;
            console.log(
                "Animations:",
                enableAnimations ? "enabled" : "disabled"
            );

            if (enableAnimations) {
                document.body.classList.remove("no-animations");
            } else {
                document.body.classList.add("no-animations");
            }
        });
    }

    // Compact mode preview
    if (compactModeToggle) {
        compactModeToggle.addEventListener("change", function () {
            const compactMode = this.checked;
            console.log("Compact mode:", compactMode ? "enabled" : "disabled");

            if (compactMode) {
                document.body.classList.add("compact-mode");
            } else {
                document.body.classList.remove("compact-mode");
            }
        });
    }

    // Debug mode toggle
    if (debugToggle) {
        debugToggle.addEventListener("change", function () {
            const debugMode = this.checked;
            console.log("Debug mode:", debugMode ? "enabled" : "disabled");

            if (debugMode) {
                document.body.classList.add("debug-mode");
            } else {
                document.body.classList.remove("debug-mode");
            }
        });
    }

    // Disable sidebar/navbar options based on navigation type
    const navigationSelectForDisable = document.querySelector('select[name="navigation_type"]');
    const sidebarOptionsWrapper = document.getElementById('sidebar-options-wrapper');
    const sidebarLockCheckbox = document.querySelector('input[name="sidebar_lock"]');
    const navbarOptionsWrapper = document.getElementById('navbar-options-wrapper');
    const stickyNavbarCheckbox = document.querySelector('input[name="sticky_navbar"]');

    function toggleNavOptions() {
        const selectedValue = navigationSelectForDisable.value;

        // Sidebar options
        if (selectedValue === 'navbar') {
            sidebarOptionsWrapper.classList.add('disabled');
            sidebarLockCheckbox.disabled = true;
            sidebarLockCheckbox.checked = false;
        } else {
            sidebarOptionsWrapper.classList.remove('disabled');
            sidebarLockCheckbox.disabled = false;
        }

        // Navbar options
        if (selectedValue === 'sidebar') {
            navbarOptionsWrapper.classList.add('disabled');
            stickyNavbarCheckbox.disabled = true;
            stickyNavbarCheckbox.checked = false;
        } else {
            navbarOptionsWrapper.classList.remove('disabled');
            stickyNavbarCheckbox.disabled = false;
        }
    }

    if (navigationSelectForDisable) {
        toggleNavOptions(); // Call on page load
        navigationSelectForDisable.addEventListener('change', toggleNavOptions);
    }

    // Sticky navbar preview
    const stickyNavbarToggle = document.querySelector('input[name="sticky_navbar"]');
    if (stickyNavbarToggle) {
        stickyNavbarToggle.addEventListener("change", function () {
            const enableStickyNavbar = this.checked;
            console.log("Sticky navbar:", enableStickyNavbar ? "enabled" : "disabled");

            if (enableStickyNavbar) {
                document.body.classList.add("sticky-navbar");
            } else {
                document.body.classList.remove("sticky-navbar");
                document.querySelector('.navbar')?.classList.remove('scrolled');
            }
        });
    }

    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (navbar && document.body.classList.contains('sticky-navbar')) {
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
    });

    // Auto-logout warning
    const autoLogoutSelect = document.querySelector(
        'select[name="auto_logout_time"]'
    );
    if (autoLogoutSelect) {
        autoLogoutSelect.addEventListener("change", function () {
            const minutes = parseInt(this.value);
            if (minutes > 0 && minutes < 30) {
                if (
                    !confirm(
                        `Warning: Setting auto-logout to ${minutes} minutes may interrupt your work. Are you sure you want to continue?`
                    )
                ) {
                    this.value = "60"; // Reset to default
                }
            }
        });
    }

    // Notification settings interaction
    const soundNotifications = document.querySelector(
        'input[name="enable_sound_notifications"]'
    );
    const browserNotifications = document.querySelector(
        'input[name="enable_browser_notifications"]'
    );

    if (browserNotifications) {
        browserNotifications.addEventListener("change", function () {
            if (this.checked) {
                // Request notification permission
                if (
                    "Notification" in window &&
                    Notification.permission === "default"
                ) {
                    Notification.requestPermission().then(function (
                        permission
                    ) {
                        if (permission !== "granted") {
                            browserNotifications.checked = false;
                            alert(
                                "Browser notifications require permission to work properly."
                            );
                        }
                    });
                }
            }
        });
    }

    // Language change warning
    const languageSelect = document.querySelector(
        'select[name="system_language"]'
    );
    if (languageSelect) {
        const originalLanguage = languageSelect.value;
        languageSelect.addEventListener("change", function () {
            if (this.value !== originalLanguage) {
                const langNames = {
                    en: "English",
                    id: "Bahasa Indonesia",
                };
                if (
                    confirm(
                        `Changing language to ${
                            langNames[this.value]
                        } will reload the page. Continue?`
                    )
                ) {
                    console.log(
                        "Language will change to:",
                        langNames[this.value]
                    );
                } else {
                    this.value = originalLanguage; // Reset to original
                }
            }
        });
    }

    // Form submission handler
    const settingsForm = document.getElementById("systemSettingsForm");
    if (settingsForm) {
        settingsForm.addEventListener("submit", function (e) {
            // Show loading state
            const submitBtn = settingsForm.querySelector(
                'button[type="submit"]'
            );
            if (submitBtn) {
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

                // Reset button after a delay (in case of errors)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 10000);
            }
        });
    }
});
