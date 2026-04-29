
import { onPageLoad } from '../utils/onPageLoad.js';

async function fetchSystemSettings() {
    try {
        const response = await fetch('/admin/api/settings');
        if (!response.ok) {
            throw new Error('Failed to fetch system settings');
        }
        const settings = await response.json();
        window.userSettings = settings;
        document.dispatchEvent(new Event('usersettingsloaded'));
    } catch (error) {
        window.userSettings = {
            enable_sound_notifications: true,
            show_success_messages: true,
            notification_duration: 5,
        };
    }

    if (window.userSettings) {
        applyPerformanceSettings(window.userSettings);
        applyDebugSettings(window.userSettings);
        applyTooltipSettings(window.userSettings);
        initAutoLogout(window.userSettings);
    }

    initSettingsPage();
}

function applyPerformanceSettings(settings) {
    if (settings.enable_animations === false) {
        document.body.classList.add('no-animations');
    } else {
        document.body.classList.remove('no-animations');
    }

    if (settings.lazy_load_images === true) {
        const images = document.querySelectorAll('img:not([loading])');
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.setAttribute('loading', 'lazy');
                        observer.unobserve(img);
                    }
                });
            });
            images.forEach(img => observer.observe(img));
        } else {
            images.forEach(img => img.setAttribute('loading', 'lazy'));
        }
    }

    if (window.dataRefreshInterval) {
        clearInterval(window.dataRefreshInterval);
    }
    const refreshRate = parseInt(settings.data_refresh_rate, 10);
    if (refreshRate > 0) {
        window.dataRefreshInterval = setInterval(() => {
            document.dispatchEvent(new CustomEvent('datarefresh'));
        }, refreshRate * 1000);
    }
}

function applyDebugSettings(settings) {
    if (settings.enable_debug_mode === true) {
        document.body.classList.add('debug-mode');
    } else {
        document.body.classList.remove('debug-mode');
    }
}

function applyTooltipSettings(settings) {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    tooltipTriggerList.forEach(tooltipTriggerEl => {
        const tooltipInstance = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
    });

    if (settings.show_tooltips === true) {
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

function initSettingsPage() {
    const systemSettingsForm = document.getElementById('systemSettingsForm');

    if (systemSettingsForm) {
        systemSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(systemSettingsForm);
            const saveButton = document.querySelector('button[form="systemSettingsForm"]');
            if (!saveButton) return;
            const originalButtonText = saveButton.innerHTML;

            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            fetch(systemSettingsForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const themeSelect = systemSettingsForm.querySelector('select[name="theme_mode"]');
                    if (themeSelect) {
                        localStorage.setItem('theme', themeSelect.value);
                    }
                    
                    // Store success message in sessionStorage to display after reload
                    sessionStorage.setItem('settingsSaved', 'true');
                    sessionStorage.setItem('settingsMessage', data.message);
                    
                    // Full reload to ensure all layout classes are properly applied
                    window.location.reload();
                } else {
                    InventMagApp.showToast('Error', data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                InventMagApp.showToast('Error', 'An unexpected error occurred.', 'error');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonText;
            });
        });

        if (window.shortcutManager) {
            window.shortcutManager.register('ctrl+s', () => {
                systemSettingsForm.requestSubmit();
            }, 'Save System Settings');
        }
    }

    const navigationTypeSelect = document.querySelector('select[name="navigation_type"]');
    if (navigationTypeSelect) {
        const navbarOptionsWrapper = document.getElementById('navbar-options-wrapper');
        const navbarInputs = navbarOptionsWrapper.querySelectorAll('input, select');
        const sidebarOptionsWrapper = document.getElementById('sidebar-options-wrapper');
        const sidebarInputs = sidebarOptionsWrapper.querySelectorAll('input, select');

        function toggleNavigationOptions() {
            const selectedNavigationType = navigationTypeSelect.value;

            if (selectedNavigationType === 'sidebar') {
                navbarOptionsWrapper.classList.add('disabled-option');
                navbarInputs.forEach(input => {
                    input.setAttribute('disabled', 'disabled');
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    }
                });
            } else {
                navbarOptionsWrapper.classList.remove('disabled-option');
                navbarInputs.forEach(input => {
                    input.removeAttribute('disabled');
                });
            }

            if (selectedNavigationType === 'navbar') {
                sidebarOptionsWrapper.classList.add('disabled-option');
                sidebarInputs.forEach(input => {
                    input.setAttribute('disabled', 'disabled');
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    }
                });
            } else {
                sidebarOptionsWrapper.classList.remove('disabled-option');
                sidebarInputs.forEach(input => {
                    input.removeAttribute('disabled');
                });
            }
        }

        toggleNavigationOptions();
        navigationTypeSelect.addEventListener('change', toggleNavigationOptions);
    }

    const resetButton = document.getElementById('resetButton');
    if (resetButton) {
        resetButton.addEventListener('click', resetToDefaults);
    }

    const showShortcutsModalBtn = document.getElementById('showShortcutsModalBtn');
    if (showShortcutsModalBtn) {
        showShortcutsModalBtn.addEventListener('click', () => {
            if (window.shortcutManager) {
                window.shortcutManager.showShortcutsModal();
            }
        });
    }
}

function initAutoLogout(settings) {
    if (window.autoLogoutInitialized) return;
    window.autoLogoutInitialized = true;

    let logoutTimer;
    let modalVisible = false;
    const autoLogoutTime = parseFloat(settings.auto_logout_time) * 60 * 1000;

    if (!autoLogoutTime || autoLogoutTime <= 0) {
        return;
    }

    function showInactivityModal() {
        if (modalVisible) return;
        modalVisible = true;

        const modalHtml = `
            <div class="modal modal-blur fade" id="inactivityModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content rounded-3 shadow-lg border-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-status bg-warning"></div>
                        <div class="modal-body text-center py-5 px-4">
                            <i class="ti ti-alert-triangle text-warning d-block mx-auto mb-3" style="font-size: 3rem;"></i>
                            <h3 class="fw-bold mb-3" style="font-size: 1.15rem;">You have been inactive</h3>
                            <div class="text-muted" style="font-size: 1.1rem; line-height: 1.5;">Do you want to stay logged in?</div>
                        </div>
                        <div class="modal-footer d-flex">
                            <button type="button" class="btn btn-secondary flex-grow-1" id="stayLoggedInBtn" data-bs-dismiss="modal">Stay</button>
                            <button type="button" class="btn btn-danger flex-grow-1" id="logoutBtn">Logout</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modalElement = document.getElementById('inactivityModal');
        const modal = new bootstrap.Modal(modalElement, {});
        modal.show();

        const stayLoggedInBtn = document.getElementById('stayLoggedInBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        if (stayLoggedInBtn) {
            stayLoggedInBtn.addEventListener('click', () => {
                resetLogoutTimer();
            });
        }
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/logout';
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            });
        }

        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
            modalVisible = false;
        });
    }

    function resetLogoutTimer() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(showInactivityModal, autoLogoutTime);
    }

    window.addEventListener('mousemove', resetLogoutTimer, { passive: true });
    window.addEventListener('keydown', resetLogoutTimer, { passive: true });
    window.addEventListener('click', resetLogoutTimer, { passive: true });
    window.addEventListener('scroll', resetLogoutTimer, { passive: true });

    resetLogoutTimer();
}

function resetToDefaults() {
    const form = document.getElementById('systemSettingsForm');
    if (!form) return;
    
    const setValue = (selector, value) => {
        const el = form.querySelector(selector);
        if (el) {
            if (el.type === 'checkbox') {
                el.checked = value;
            } else {
                el.value = value;
            }
        }
    };

    setValue('select[name="navigation_type"]', 'sidebar');
    setValue('input[name="sidebar_lock"]', false);
    setValue('input[name="sticky_navbar"]', false);
    setValue('select[name="theme_mode"]', 'light');
    setValue('input[name="show_theme_toggle"]', true);
    setValue('input[name="enable_sound_notifications"]', true);
    setValue('input[name="enable_browser_notifications"]', true);
    setValue('input[name="show_success_messages"]', true);
    setValue('select[name="notification_duration"]', '5');
    setValue('select[name="auto_logout_time"]', '60');
    setValue('input[name="remember_last_page"]', true);
    setValue('input[name="enable_animations"]', true);
    setValue('input[name="lazy_load_images"]', true);
    setValue('input[name="fluid_layout"]', false);
    setValue('select[name="data_refresh_rate"]', '30');
    setValue('select[name="system_language"]', 'en');
    setValue('input[name="enable_debug_mode"]', false);
    setValue('input[name="enable_keyboard_shortcuts"]', true);
    setValue('input[name="show_tooltips"]', true);

    const navigationTypeSelect = form.querySelector('select[name="navigation_type"]');
    if (navigationTypeSelect) {
        navigationTypeSelect.dispatchEvent(new Event('change'));
    }

    InventMagApp.showToast('Success', 'Settings have been reset to their default values.', 'success');
}

function handleNotificationsOnLoad() {
    if (window.handleSessionNotifications) {
        window.handleSessionNotifications();
    }
    
    // Check for settings saved message from sessionStorage (after page reload)
    const settingsSaved = sessionStorage.getItem('settingsSaved');
    const settingsMessage = sessionStorage.getItem('settingsMessage');
    
    if (settingsSaved === 'true') {
        InventMagApp.showToast('Success', settingsMessage || 'Settings saved successfully.', 'success');
        sessionStorage.removeItem('settingsSaved');
        sessionStorage.removeItem('settingsMessage');
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');

    if (status && message) {
        if (status === 'success') {
            InventMagApp.showToast('Success', decodeURIComponent(message), 'success');
        } else if (status === 'error') {
            InventMagApp.showToast('Error', decodeURIComponent(message), 'error');
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

onPageLoad(() => {
    if (document.querySelector('meta[name="csrf-token"]')) {
        fetchSystemSettings();
    }
    handleNotificationsOnLoad();
});
