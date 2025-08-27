document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing sidebar...");

    const sidebar = document.querySelector(".sidebar");
    const sidebarToggleInternal = document.getElementById(
        "sidebar-toggle-internal"
    );
    const mainContent = document.querySelector(".main-content");

    console.log("Sidebar:", sidebar);
    console.log("Toggle button:", sidebarToggleInternal);
    console.log("Main content:", mainContent);

    if (!sidebar || !sidebarToggleInternal || !mainContent) {
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

    sidebarToggleInternal.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Toggle button clicked");
        toggleSidebar();
    });
});
