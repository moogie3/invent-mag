document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing sidebar...");

    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById(
        "sidebar-toggle"
    );
    const mainContent = document.querySelector(".main-content");

    console.log("Sidebar:", sidebar);
    console.log("Toggle button:", sidebarToggle);
    console.log("Main content:", mainContent);

    if (!sidebar || !mainContent) {
        console.error("Required elements not found!");
        return;
    }

    const toggleSidebar = function () {
        console.log("Toggling sidebar...");
        sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("sidebar-collapsed");

        // Store sidebar state in localStorage
        const isCollapsed = sidebar.classList.contains("collapsed");
        localStorage.setItem("sidebar-collapsed", isCollapsed);
        console.log("Sidebar collapsed:", isCollapsed);
    };

    // Apply saved sidebar state on page load
    const savedState = localStorage.getItem("sidebar-collapsed");
    if (savedState === "true") {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("sidebar-collapsed");
        console.log("Applied saved collapsed state");
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            console.log("Toggle button clicked");
            toggleSidebar();
        });
    }
});
