function resetToDefaults() {
    if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
        // Reset form to default values
        document.getElementById('systemSettingsForm').reset();

        // Set specific default values
        document.querySelector('select[name="navigation_type"]').value = 'sidebar';
        document.querySelector('select[name="theme_mode"]').value = 'light';
        document.querySelector('select[name="records_per_page"]').value = '25';
        document.querySelector('select[name="date_format"]').value = 'Y-m-d';
        document.querySelector('select[name="time_format"]').value = 'H:i:s';
        document.querySelector('select[name="auto_logout_time"]').value = '60';
        document.querySelector('select[name="notification_duration"]').value = '5';
        document.querySelector('select[name="system_language"]').value = 'en';
        document.querySelector('select[name="number_format"]').value = '1234.56';
        document.querySelector('select[name="currency_position"]').value = 'before';
        document.querySelector('select[name="data_refresh_rate"]').value = '30';

        // Set checkboxes to default values
        document.querySelector('input[name="sidebar_lock"]').checked = false;
        document.querySelector('input[name="show_theme_toggle"]').checked = true;
        document.querySelector('input[name="enable_sound_notifications"]').checked = true;
        document.querySelector('input[name="enable_browser_notifications"]').checked = true;
        document.querySelector('input[name="show_success_messages"]').checked = true;
        document.querySelector('input[name="remember_last_page"]').checked = true;
        document.querySelector('input[name="enable_animations"]').checked = true;
        document.querySelector('input[name="lazy_load_images"]').checked = true;
        document.querySelector('input[name="enable_debug_mode"]').checked = false;
        document.querySelector('input[name="enable_keyboard_shortcuts"]').checked = true;
        document.querySelector('input[name="show_tooltips"]').checked = true;
        document.querySelector('input[name="compact_mode"]').checked = false;
    }
}

// Apply settings preview (optional)
document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.querySelector('select[name="theme_mode"]');
    const animationToggle = document.querySelector('input[name="enable_animations"]');

    // You can add preview functionality here
    themeSelect.addEventListener('change', function() {
        // Preview theme change
        console.log('Theme changed to:', this.value);
    });

    animationToggle.addEventListener('change', function() {
        // Preview animation toggle
        console.log('Animations:', this.checked ? 'enabled' : 'disabled');
    });
});
