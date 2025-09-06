document.addEventListener("DOMContentLoaded", function () {
    // Toggle password visibility
    if (document.getElementById("toggle-password")) {
        const passwordField = document.getElementById("password");
        const togglePassword = document.getElementById("toggle-password");
        const toggleIcon = togglePassword.querySelector("i");

        togglePassword.addEventListener("click", function (e) {
            e.preventDefault();
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("ti-eye");
                toggleIcon.classList.add("ti-eye-off");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("ti-eye-off");
                toggleIcon.classList.add("ti-eye");
            }
        });
    }

    // Show content after loading
    setTimeout(function () {
        const loadingContainer = document.getElementById("loading-container");
        const authContent = document.getElementById("auth-content");

        if (loadingContainer) loadingContainer.style.display = "none";
        if (authContent) authContent.style.display = "block";
    }, 800);

    // Theme toggle script
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
});