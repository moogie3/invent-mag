document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const mainContent = document.querySelector(".main-content");
    const body = document.body;

    if (!sidebar || !mainContent) {
        console.error("Required elements not found!");
        return;
    }

    // Sidebar lock state
    let sidebarLocked = false;

    // Create lock button
    const createLockButton = function () {
        const lockButton = document.createElement("button");
        lockButton.className = "sidebar-lock-btn";
        lockButton.innerHTML = '<i class="ti ti-lock-open"></i>';
        lockButton.title = "Lock sidebar (prevents auto-close)";

        const sidebarHeader = sidebar.querySelector(".sidebar-header");
        if (sidebarHeader) {
            sidebarHeader.style.position = "relative";
            sidebarHeader.appendChild(lockButton);
        }

        return lockButton;
    };

    const lockButton = createLockButton();

    // Toggle lock functionality
    const toggleLock = function () {
        sidebarLocked = !sidebarLocked;
        const icon = lockButton.querySelector("i");

        if (sidebarLocked) {
            icon.className = "ti ti-lock";
            lockButton.classList.add("locked");
            lockButton.classList.remove("unlocked");
            lockButton.title = "Unlock sidebar (allows auto-close)";
        } else {
            icon.className = "ti ti-lock-open";
            lockButton.classList.add("unlocked");
            lockButton.classList.remove("locked");
            lockButton.title = "Lock sidebar (prevents auto-close)";
        }
    };

    // Lock button event
    lockButton.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleLock();
    });

    const tooltipManager = {
        tooltips: new Map(),

        create(element, text) {
            if (this.tooltips.has(element)) return;

            const tooltip = document.createElement("div");
            tooltip.className = "sidebar-tooltip";
            tooltip.textContent = text;

            document.body.appendChild(tooltip);
            this.tooltips.set(element, tooltip);

            const showTooltip = (e) => {
                if (!sidebar.classList.contains("collapsed")) return;

                const rect = element.getBoundingClientRect();
                tooltip.style.left = rect.right + 10 + "px";
                tooltip.style.top =
                    rect.top +
                    rect.height / 2 -
                    tooltip.offsetHeight / 2 +
                    "px";
                tooltip.classList.add("show");
            };

            const hideTooltip = () => {
                tooltip.classList.remove("show");
            };

            element.addEventListener("mouseenter", showTooltip);
            element.addEventListener("mouseleave", hideTooltip);
        },

        removeAll() {
            this.tooltips.forEach((tooltip, element) => {
                tooltip.remove();
            });
            this.tooltips.clear();
        },
    };

    // Add enhanced tooltips with staggered animation
    const addTooltips = function () {
        const navLinks = sidebar.querySelectorAll(".nav-link");
        navLinks.forEach((link, index) => {
            const titleElement = link.querySelector(".nav-link-title");
            if (titleElement) {
                // Add staggered fade-in animation for collapsed state
                link.style.animationDelay = `${index * 50}ms`;
                tooltipManager.create(link, titleElement.textContent.trim());
            }
        });
    };

    // Enhanced sidebar toggle with fast closing, smooth opening
    const toggleSidebar = function () {
        const isCollapsed = sidebar.classList.contains("collapsed");
        const willCollapse = !isCollapsed;

        const navTexts = sidebar.querySelectorAll(".nav-link-title");

        if (willCollapse) {
            // FAST closing - immediate collapse with quick transition
            sidebar.classList.add("sidebar-fast-close");
            mainContent.classList.add("main-content-fast-close");

            // Quickly hide text and collapse
            navTexts.forEach((text) => {
                text.classList.add("nav-text-fade-out");
            });

            // Immediate collapse
            setTimeout(() => {
                sidebar.classList.add("collapsed");
                mainContent.classList.add("sidebar-collapsed");
                body.classList.remove("sidebar-open");
                addTooltips();
            }, 50);
        } else {
            // SMOOTH opening - keep the nice opening animation
            sidebar.classList.remove("sidebar-fast-close");
            sidebar.classList.add("sidebar-smooth-open");
            mainContent.classList.remove("main-content-fast-close");
            mainContent.classList.add("main-content-smooth-open");

            sidebar.classList.remove("collapsed");
            mainContent.classList.remove("sidebar-collapsed");
            body.classList.add("sidebar-open");

            // Staggered fade-in animation for text
            setTimeout(() => {
                navTexts.forEach((text, index) => {
                    text.classList.remove("nav-text-fade-out");
                    text.classList.add("nav-text-slide-in");

                    setTimeout(() => {
                        text.classList.add("visible");
                    }, index * 30);
                });
            }, 100);

            tooltipManager.removeAll();
        }
    };

    // Initialize with sidebar closed and smooth entrance animation
    const initializeSidebar = function () {
        // Set initial state - always start collapsed
        sidebar.classList.add("collapsed");
        mainContent.classList.add("sidebar-collapsed");
        body.classList.remove("sidebar-open");

        // Animate elements into view with faster menu icon
        const navItems = sidebar.querySelectorAll(".nav-link");
        navItems.forEach((item, index) => {
            item.classList.add("nav-item-animate");

            setTimeout(() => {
                item.classList.add("visible");
            }, index * 40);
        });

        setTimeout(addTooltips, navItems.length * 40 + 100);
    };

    // Click outside to close sidebar (respects lock)
    const handleClickOutside = function (e) {
        const isOpen = !sidebar.classList.contains("collapsed");

        if (
            isOpen &&
            !sidebarLocked && // Only close if not locked
            !sidebar.contains(e.target) &&
            !sidebarToggle?.contains(e.target)
        ) {
            toggleSidebar();
        }
    };

    // Initialize everything
    initializeSidebar();

    // Toggle button event
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Add click outside listener
    document.addEventListener("click", handleClickOutside);

    // Enhanced responsive behavior
    let resizeTimeout;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.innerWidth < 768) {
                if (!sidebar.classList.contains("collapsed")) {
                    toggleSidebar();
                }
            }
        }, 150);
    });

    // Add keyboard support
    document.addEventListener("keydown", function (e) {
        // Toggle sidebar with Ctrl/Cmd + B
        if ((e.ctrlKey || e.metaKey) && e.key === "b") {
            e.preventDefault();
            toggleSidebar();
        }
    });
});
