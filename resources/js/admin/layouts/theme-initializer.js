(function() {
    /*
     * Every interactive layout already decides the theme server-side:
     *   base.blade.php  -> data-bs-theme="{{ $themeMode }}"
     *   authbase / login -> applied from localStorage before paint
     *
     * That value is authoritative: theme-toggle.js writes every change back to
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
    const provided = root.getAttribute('data-bs-theme');

    if (provided) {
        /*
         * A layout rendered the server's choice: copy it into localStorage so
         * the guest pages (login, error) agree with it on the next visit. The
         * guard skips pages that just read FROM storage, so an OS guess is
         * never frozen in as if the user had picked it.
         */
        if (!window.__inventThemeFromStorage) {
            try {
                if (localStorage.getItem('theme') !== provided) {
                    localStorage.setItem('theme', provided);
                }
            } catch (e) {}
        }
        return;
    }

    const storedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    root.setAttribute('data-bs-theme', storedTheme || (prefersDark ? 'dark' : 'light'));
})();
