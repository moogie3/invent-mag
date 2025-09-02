import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

import "./admin/profile";
import "./admin/category";
import "./admin/unit";
import "./admin/customer";
import "./admin/purchase-order";
import "./admin/pos";
import "./admin/recentts";
import "./admin/sales-order";
import "./admin/supplier";
import "./admin/user";
import "./admin/warehouse";
import "./admin/helpers/notification";
// Don't import theme-toggle here since it's loaded separately

// Function to apply theme based on mode (light, dark, auto)
window.applyTheme = function (themeMode) {
    const body = document.body;
    let appliedTheme = themeMode;

    console.log("Applying theme mode:", themeMode);

    if (themeMode === "auto") {
        appliedTheme = window.matchMedia("(prefers-color-scheme: dark)").matches
            ? "dark"
            : "light";
        console.log("Auto theme resolved to:", appliedTheme);
    }

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

    // Listen for system theme changes when in auto mode
    window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", function (e) {
            const currentMode =
                document.body.getAttribute("data-theme-mode") || "light";
            console.log("System theme changed. Current mode:", currentMode);
            if (currentMode === "auto") {
                console.log("System theme changed, reapplying auto theme");
                window.applyTheme("auto");
            }
        });
});
