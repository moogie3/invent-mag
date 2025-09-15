
async function fetchSystemSettings() {
    try {
        const response = await fetch('/admin/api/settings');
        if (!response.ok) {
            throw new Error('Failed to fetch system settings');
        }
        const settings = await response.json();
        window.userSettings = settings;
        console.log('System settings loaded:', window.userSettings);
        initSettingsPage(); // Initialize the page after settings are loaded
    } catch (error) {
        console.error('Error fetching system settings:', error);
        // Fallback to default settings if fetch fails
        window.userSettings = {
            enable_sound_notifications: true,
            show_success_messages: true,
            notification_duration: 5000,
        };
        initSettingsPage(); // Initialize the page even if settings fetch fails
    }
}

function initSettingsPage() {
    const systemSettingsForm = document.getElementById('systemSettingsForm');

    if (systemSettingsForm) {
        systemSettingsForm.addEventListener('submit', function (e) {
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
                    showToast('Success', data.message, 'success');
                } else {
                    showToast('Error', data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'An unexpected error occurred.', 'error');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonText;
            });
        });
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

// Initialize settings on script load
fetchSystemSettings();