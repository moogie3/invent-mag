(function() {
    /*
     * Every interactive layout already decides the theme server-side:
     *   base.blade.php / superadmin base  -> data-bs-theme="{{ $themeMode }}"
     *   authbase / errorbase             -> hardcoded
     *
     * That value is authoritative — theme-toggle.js writes every change back to
     * /admin/settings/update-theme-mode, so the server always holds the user's
     * current choice. Only reach for localStorage/OS when no layout provided one.
     *
     * Previously this overwrote the server attribute unconditionally, so any
     * browser with empty localStorage (new device, cleared storage, headless
     * run) fell through to the OS preference and silently discarded the saved
     * setting: theme_mode could be "dark" on the settings page while the app
     * itself came up light.
     */
    const root = document.documentElement;
    if (root.getAttribute('data-bs-theme')) {
        return;
    }

    const storedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    root.setAttribute('data-bs-theme', storedTheme || (prefersDark ? 'dark' : 'light'));
})();
