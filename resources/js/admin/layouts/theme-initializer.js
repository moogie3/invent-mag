(function() {
    const getTheme = () => {
        // 1. Check for a theme in localStorage
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            return storedTheme;
        }

        // 2. Check for the OS-level preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }

        // 3. Default to 'light'
        return 'light';
    };

    const theme = getTheme();
    document.documentElement.setAttribute('data-bs-theme', theme);
})();
