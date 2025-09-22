
async function fetchSystemSettings() {
    try {
        const response = await fetch('/admin/api/settings');
        if (!response.ok) {
            throw new Error('Failed to fetch system settings');
        }
        const settings = await response.json();
        window.userSettings = settings;
        console.log('System settings loaded:', window.userSettings);
    } catch (error) {
        console.error('Error fetching system settings:', error);
        // Fallback to default settings if fetch fails
        window.userSettings = {
            enable_sound_notifications: true,
            show_success_messages: true,
            notification_duration: 5,
        };
    }

    // Once settings are loaded, check for and display any session-based notifications.
    if (window.handleSessionNotifications) {
        window.handleSessionNotifications();
    }

    // Initialize features that depend on these settings
    if (window.userSettings) {
        applyPerformanceSettings(window.userSettings);
        initAutoLogout(window.userSettings);
    }

    // Initialize the settings page functionality if the form exists on the current page.
    initSettingsPage();
}

/**
 * Applies global performance settings based on user preferences.
 * @param {object} settings - The user settings object.
 */
function applyPerformanceSettings(settings) {
    // 1. UI Animations
    if (settings.enable_animations === false) {
        document.body.classList.add('no-animations');
    } else {
        document.body.classList.remove('no-animations');
    }

    // 2. Lazy Load Images
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
            // Fallback for older browsers
            images.forEach(img => img.setAttribute('loading', 'lazy'));
        }
    }

    // 3. Data Refresh Rate
    if (window.dataRefreshInterval) {
        clearInterval(window.dataRefreshInterval);
    }
    const refreshRate = parseInt(settings.data_refresh_rate, 10);
    if (refreshRate > 0) {
        window.dataRefreshInterval = setInterval(() => {
            document.dispatchEvent(new CustomEvent('datarefresh'));
            console.log('Dispatched datarefresh event.');
        }, refreshRate * 1000);
    }
}

function initSettingsPage() {
    const systemSettingsForm = document.getElementById('systemSettingsForm');

    if (systemSettingsForm) {
        /* systemSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(systemSettingsForm);
            const saveButton = document.querySelector('button[form="systemSettingsForm"]');
            const originalButtonText = saveButton.innerHTML;

            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            fetch(systemSettingsForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest', // Important for Laravel to recognize it as an AJAX request
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update global settings object FIRST
                    if (window.userSettings) {
                        for (const key in data.settings) {
                            if (Object.hasOwnProperty.call(data.settings, key)) {
                                window.userSettings[key] = data.settings[key];
                            }
                        }
                    }
                    InventMagApp.showToast('Success', data.message, 'success');
                } else {
                    InventMagApp.showToast('Error', data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                InventMagApp.showToast('Error', 'An unexpected error occurred.', 'error');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonText;
            });
        }); */
    }

    const navigationTypeSelect = document.querySelector('select[name="navigation_type"]');
    if (navigationTypeSelect) {
        const navbarOptionsWrapper = document.getElementById('navbar-options-wrapper');
        const navbarInputs = navbarOptionsWrapper.querySelectorAll('input, select');

        const sidebarOptionsWrapper = document.getElementById('sidebar-options-wrapper');
        const sidebarInputs = sidebarOptionsWrapper.querySelectorAll('input, select');

        function toggleNavigationOptions() {
            const selectedNavigationType = navigationTypeSelect.value;

            // Handle Navbar Options
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

            // Handle Sidebar Options
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

        // Initial check on page load
        toggleNavigationOptions();

        // Add event listener for changes
        navigationTypeSelect.addEventListener('change', toggleNavigationOptions);
    }
}


// Add a new function to initialize the auto-logout feature
function initAutoLogout(settings) {
    let logoutTimer;
    let modalVisible = false;
    // Convert minutes to milliseconds
    const autoLogoutTime = parseFloat(settings.auto_logout_time) * 60 * 1000;

    // If logout time is 0 or not a number, disable the feature
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
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        document.getElementById('stayLoggedInBtn').addEventListener('click', () => {
            resetLogoutTimer();
        });

        document.getElementById('logoutBtn').addEventListener('click', () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/logout';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
            modalVisible = false;
        });
    }

    function resetLogoutTimer() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(showInactivityModal, autoLogoutTime);
    }

    // Attach event listeners
    window.addEventListener('mousemove', resetLogoutTimer, { passive: true });
    window.addEventListener('keydown', resetLogoutTimer, { passive: true });
    window.addEventListener('click', resetLogoutTimer, { passive: true });
    window.addEventListener('scroll', resetLogoutTimer, { passive: true });

    // Initial timer start
    resetLogoutTimer();
}

// Initialize settings on script load
if (document.querySelector('meta[name="csrf-token"]')) {
    fetchSystemSettings();
}
