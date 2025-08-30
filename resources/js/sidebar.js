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

        lockButton.style.cssText = `
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
        `;

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
            lockButton.style.background = "rgba(59, 130, 246, 0.2)";
            lockButton.style.color = "#3b82f6";
            lockButton.title = "Unlock sidebar (allows auto-close)";
        } else {
            icon.className = "ti ti-lock-open";
            lockButton.style.background = "rgba(255, 255, 255, 0.1)";
            lockButton.style.color = "rgba(255, 255, 255, 0.7)";
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
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 1000;
                opacity: 0;
                transform: translateX(10px);
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                pointer-events: none;
                backdrop-filter: blur(8px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            `;

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
                tooltip.style.opacity = "1";
                tooltip.style.transform = "translateX(0)";
            };

            const hideTooltip = () => {
                tooltip.style.opacity = "0";
                tooltip.style.transform = "translateX(10px)";
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
            sidebar.style.transition = "all 0.15s ease-out";
            mainContent.style.transition = "margin-left 0.15s ease-out";

            // Instantly hide text and collapse
            navTexts.forEach((text) => {
                text.style.transition = "opacity 0.1s ease-out";
                text.style.opacity = "0";
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
            sidebar.style.transition = "all 0.4s cubic-bezier(0.4, 0, 0.2, 1)";
            mainContent.style.transition =
                "margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1)";

            sidebar.classList.remove("collapsed");
            mainContent.classList.remove("sidebar-collapsed");
            body.classList.add("sidebar-open");

            // Staggered fade-in animation for text
            setTimeout(() => {
                navTexts.forEach((text, index) => {
                    text.style.opacity = "0";
                    text.style.transform = "translateX(-20px)";

                    setTimeout(() => {
                        text.style.transition =
                            "all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1)"; // Faster text animation
                        text.style.opacity = "1";
                        text.style.transform = "translateX(0)";
                    }, index * 30); // Reduced delay
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
            item.style.opacity = "0";
            item.style.transform = "translateY(20px)";

            setTimeout(() => {
                item.style.transition =
                    "all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1)"; // Faster transition
                item.style.opacity = "1";
                item.style.transform = "translateY(0)";
            }, index * 40); // Reduced delay
        });

        setTimeout(addTooltips, navItems.length * 40 + 100);
    };

    // Add CSS for smooth transitions
    const addSmoothStyles = function () {
        const style = document.createElement("style");
        style.textContent = `
            .nav-link {
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                position: relative;
                overflow: hidden;
            }

            .nav-link::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
                transition: left 0.3s;
                z-index: 1;
            }

            .nav-link:hover::before {
                left: 100%;
            }

            .nav-link-title {
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                display: inline-block;
            }

            .sidebar.collapsed .nav-link {
                justify-content: center;
            }

            .sidebar.collapsed .nav-link:hover {
                transform: scale(1.03);
                background-color: rgba(255, 255, 255, 0.1);
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.03); }
            }

            .sidebar-toggle:hover {
                animation: pulse 0.3s ease-in-out;
            }

            /* Micro-interactions */
            .nav-link {
                transform-origin: center;
            }

            .sidebar-lock-btn:hover {
                background: rgba(255, 255, 255, 0.15) !important;
                transform: scale(1.05);
            }

            .sidebar-lock-btn:active {
                transform: scale(0.95);
            }

            .sidebar.collapsed .sidebar-lock-btn {
                opacity: 0;
                pointer-events: none;
            }
        `;
        document.head.appendChild(style);
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
    addSmoothStyles();
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

    // Smooth scroll behavior for better overall UX
    document.documentElement.style.scrollBehavior = "smooth";
});
