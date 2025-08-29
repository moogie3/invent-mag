document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const mainContent = document.querySelector(".main-content");
    const body = document.body;

    if (!sidebar || !mainContent) {
        console.error("Required elements not found!");
        return;
    }

    // Add title attributes for tooltips when collapsed
    const addTooltips = function () {
        const navLinks = sidebar.querySelectorAll(".nav-link");
        navLinks.forEach((link) => {
            const titleElement = link.querySelector(".nav-link-title");
            if (titleElement && !link.getAttribute("title")) {
                link.setAttribute("title", titleElement.textContent.trim());
            }
        });
    };

    const toggleSidebar = function () {
        const isCollapsed = sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("sidebar-collapsed", isCollapsed);
        body.classList.toggle("sidebar-open", !isCollapsed);

        // Store sidebar state in localStorage (but don't use it for actual storage in Claude)
        // localStorage.setItem("sidebar-collapsed", isCollapsed);

        // Add tooltips after collapse animation completes
        if (isCollapsed) {
            setTimeout(addTooltips, 300);
        }
    };

    // Initialize tooltips on page load
    addTooltips();

    const savedState = localStorage.getItem("sidebar-collapsed");
    if (savedState === "true") {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("sidebar-collapsed");
        body.classList.remove("sidebar-open");
        setTimeout(addTooltips, 100);
    } else if (savedState === "false") {
        sidebar.classList.remove("collapsed");
        mainContent.classList.remove("sidebar-collapsed");
        body.classList.add("sidebar-open");
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Handle window resize to ensure responsive behavior
    window.addEventListener("resize", function () {
        if (window.innerWidth < 768) {
            if (!sidebar.classList.contains("collapsed")) {
                sidebar.classList.add("collapsed");
                mainContent.classList.add("sidebar-collapsed");
                addTooltips();
            }
        }
    });
});
