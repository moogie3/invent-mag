import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

// Admin-specific scripts are now conditionally loaded in script.blade.php
// to prevent double-loading and improve performance.

// Function to apply theme based on mode (light, dark, auto)
window.applyTheme = function (themeMode) {
    const body = document.body;
    let appliedTheme = themeMode;

    console.log("Applying theme mode:", themeMode);

    

    // Set the data-bs-theme attribute for Bootstrap dark mode
    body.setAttribute("data-bs-theme", appliedTheme);

    // Store the theme mode for reference
    body.setAttribute("data-theme-mode", themeMode);

    // Add/remove 'theme-dark' class for compatibility with app.css
    if (appliedTheme === "dark") {
        body.classList.add("theme-dark");
        body.classList.add("dark-mode");
        body.classList.remove("light-mode");
    } else {
        body.classList.remove("theme-dark");
        body.classList.remove("dark-mode");
        body.classList.add("light-mode");
    }

    console.log("Theme applied. Body classes:", body.classList.toString());
    console.log("data-bs-theme:", body.getAttribute("data-bs-theme"));
    console.log("data-theme-mode:", body.getAttribute("data-theme-mode"));

    // Trigger a custom event to notify other parts of the app
    window.dispatchEvent(
        new CustomEvent("themeChanged", {
            detail: { theme: appliedTheme, mode: themeMode },
        })
    );
};

// Apply initial theme on page load
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, applying initial theme...");

    // Get the initial theme mode from the body's data-bs-theme attribute
    let initialThemeMode =
        document.body.getAttribute("data-bs-theme") || "light";

    console.log("Initial theme mode from body:", initialThemeMode);

    // Apply the theme
    window.applyTheme(initialThemeMode);

    // Handle navbar scroll effects
    const navbar = document.querySelector(".navbar");

    if (navbar) {
        let lastScrollTop = 0;
        let isScrolled = false;

        window.addEventListener("scroll", function () {
            const scrollTop =
                window.pageYOffset || document.documentElement.scrollTop;

            // Add 'scrolled' class when user scrolls down
            if (scrollTop > 10 && !isScrolled) {
                navbar.classList.add("scrolled");
                isScrolled = true;
            } else if (scrollTop <= 10 && isScrolled) {
                navbar.classList.remove("scrolled");
                isScrolled = false;
            }

            lastScrollTop = scrollTop;
        });
    }

    
});
