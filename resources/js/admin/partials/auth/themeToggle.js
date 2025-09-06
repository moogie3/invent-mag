export function themeToggle() {
    const themeToggleButton = document.getElementById('theme-toggle-button');
    const themeIcon = document.getElementById('theme-icon');

    // Set initial theme based on localStorage or default to light
    let currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', currentTheme);
    if (currentTheme === 'dark') {
        themeIcon.classList.replace('ti-moon', 'ti-sun');
    } else {
        themeIcon.classList.replace('ti-sun', 'ti-moon');
    }

    themeToggleButton.addEventListener('click', () => {
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            localStorage.setItem('theme', 'light');
            themeIcon.classList.replace('ti-sun', 'ti-moon');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            themeIcon.classList.replace('ti-moon', 'ti-sun');
        }
    });
}
