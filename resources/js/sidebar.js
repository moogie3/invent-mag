document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const mainContent = document.querySelector(".main-content");
    const body = document.body;

    if (!sidebar || !mainContent) {
        console.error("Required elements not found!");
        return;
    }

    const toggleSidebar = function () {
        const isCollapsed = sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("sidebar-collapsed");
        body.classList.toggle("sidebar-open", !isCollapsed);

        // Store sidebar state in localStorage
        localStorage.setItem("sidebar-collapsed", isCollapsed);
    };

    // Apply saved sidebar state on page load
    const savedState = localStorage.getItem("sidebar-collapsed");
    if (savedState === "true") {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("sidebar-collapsed");
        body.classList.remove("sidebar-open");
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
});
