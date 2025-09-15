
// Fetch system settings and store them in a global object
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
            notification_duration: 5000,
        };
    }
}

// Initialize settings on script load
fetchSystemSettings();
