// Track when the last Turbo navigation completed (performance.now() timestamp).
// Used by handleClickOutside to ignore the stale click that triggered the navigation —
// without this, the nav-link click bleeds into the new page and immediately closes the sidebar.
let lastTurboLoadTime = 0;

document.addEventListener("usersettingsloaded", function () {
    initSidebar();
    syncActiveState();
});

// Also re-init after Turbo navigation
document.addEventListener("turbo:load", function () {
    lastTurboLoadTime = performance.now();
    initSidebar();
    syncActiveState();
});

function syncActiveState() {
    const currentPath = window.location.pathname;
    const sidebar = document.querySelector(".sidebar");
    if (!sidebar) return;

    sidebar.querySelectorAll(".nav-link").forEach(link => {
        const href = link.getAttribute("href");
        if (href && href !== "#" && href !== currentPath + "/" && href !== currentPath) {
            link.classList.remove("active");
        }
    });

    setTimeout(() => {
        sidebar.querySelectorAll(".nav-link").forEach(link => {
            const href = link.getAttribute("href");
            if (href && href !== "#") {
                const linkPath = new URL(href, window.location.origin).pathname;
                if (linkPath === currentPath || currentPath.startsWith(linkPath + "/")) {
                    link.classList.add("active");
                }
            }
        });
    }, 50);
}

document.addEventListener('turbo:before-cache', () => {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar) {
        sidebar.dataset.turboInitialized = "";
    }
});

function initSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const mainContent = document.querySelector(".main-content");
    const body = document.body;

    if (!sidebar || !mainContent) {
        return;
    }

    // Check if already initialized
    if (sidebar.dataset.turboInitialized === "true") {
        return;
    }
    sidebar.dataset.turboInitialized = "true";

    // Create lock button
    const createLockButton = function () {
        const lockButton = document.createElement("button");
        lockButton.className = "sidebar-lock-btn";
        lockButton.innerHTML = document.body.classList.contains('sidebar-locked')
            ? '<i class="ti ti-lock"></i>'
            : '<i class="ti ti-lock-open"></i>';
        lockButton.title = document.body.classList.contains('sidebar-locked')
            ? "Unlock sidebar (allows auto-close)"
            : "Lock sidebar (prevents auto-close)";

        // Add initial locked/unlocked class
        if (document.body.classList.contains('sidebar-locked')) {
            lockButton.classList.add("locked");
        } else {
            lockButton.classList.add("unlocked");
        }

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
        document.body.classList.toggle("sidebar-locked");

        const icon = lockButton.querySelector("i");

        if (document.body.classList.contains('sidebar-locked')) {
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
        // Only add tooltips if the user setting is enabled
        if (window.userSettings && !window.userSettings.show_tooltips) {
            return; // Do not add tooltips if disabled
        }

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
            // Hide any open submenus before collapsing the sidebar
            sidebar.querySelectorAll('.collapse.show').forEach(openSubmenu => {
                const bsCollapse = bootstrap.Collapse.getInstance(openSubmenu);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });

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
                // Only add tooltips if the setting is enabled
                if (window.userSettings && window.userSettings.show_tooltips) {
                    addTooltips();
                }
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

            // Ensure all submenu items are visible when sidebar opens
            sidebar.querySelectorAll('.nav-submenu .nav-item').forEach(item => {
                item.classList.remove('visible');
                item.style.transitionDelay = '';
            });
        }
    };

    // Get persistent sidebar state
    const getSidebarState = function () {
        const savedState = localStorage.getItem("sidebarCollapsed");
        return savedState === null ? true : savedState === "true"; // Default to collapsed if no saved state
    };

    // Save sidebar state
    const saveSidebarState = function (isCollapsed) {
        localStorage.setItem("sidebarCollapsed", isCollapsed.toString());
    };

    // Initialize with persistent sidebar state
    const initializeSidebar = function () {
        const shouldBeCollapsed = getSidebarState();

        // Set initial state based on saved preference
        if (shouldBeCollapsed) {
            sidebar.classList.add("collapsed");
            mainContent.classList.add("sidebar-collapsed");
            body.classList.remove("sidebar-open");
        } else {
            sidebar.classList.remove("collapsed");
            mainContent.classList.remove("sidebar-collapsed");
            body.classList.add("sidebar-open");
        }

        // Animate elements into view with faster menu icon
        const navItems = sidebar.querySelectorAll(".nav-link");
        navItems.forEach((item, index) => {
            item.classList.add("nav-item-animate");

            setTimeout(() => {
                item.classList.add("visible");
            }, index * 80);
        });

        // Add tooltips if sidebar is collapsed AND the setting is enabled
        if (shouldBeCollapsed && window.userSettings && window.userSettings.show_tooltips) {
            setTimeout(addTooltips, navItems.length * 40 + 100);
        }

        // Apply staggered animation to initially open submenus
        sidebar.querySelectorAll('.nav-submenu.show').forEach(openSubmenu => {
            const submenuItems = openSubmenu.querySelectorAll('.nav-item');
            submenuItems.forEach((item, idx) => {
                item.style.transitionDelay = `${idx * 100}ms`;
                item.classList.add('visible');
            });
        });
    };

    // Updated toggle function to save state
    const toggleSidebarWithPersistence = function () {
        const isCollapsed = sidebar.classList.contains("collapsed");
        const willCollapse = !isCollapsed;

        // Save the new state
        saveSidebarState(willCollapse);

        // Call the existing toggle function
        toggleSidebar();
    };

    // Initialize everything
    initializeSidebar();

    // Handle clicks on parent menu items when sidebar is collapsed
    const navLinksWithChildren = sidebar.querySelectorAll('.nav-item.dropdown > .nav-link[data-bs-toggle="collapse"]');
    navLinksWithChildren.forEach(link => {
        link.addEventListener('click', function(e) {
            if (sidebar.classList.contains('collapsed')) {
                // Prevent default collapse behavior initially
                e.preventDefault();
                e.stopPropagation();

                // Expand the sidebar
                saveSidebarState(false); // Set state to expanded
                toggleSidebar();

                // After a short delay to allow sidebar to open, trigger the collapse
                setTimeout(() => {
                    // Manually trigger Bootstrap's collapse toggle
                    const targetId = link.getAttribute('data-bs-target');
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        const bsCollapse = new bootstrap.Collapse(targetElement, {
                            toggle: false // Do not toggle immediately
                        });

                        // Listen for when the submenu is fully shown by Bootstrap
                        targetElement.addEventListener('shown.bs.collapse', function handler() {
                            // Staggered animation for submenu items
                            const submenuItems = targetElement.querySelectorAll('.nav-item');
                            submenuItems.forEach((item, idx) => {
                                item.style.transitionDelay = `${idx * 100}ms`; // Stagger delay
                                item.classList.add('visible');
                            });
                            targetElement.removeEventListener('shown.bs.collapse', handler);
                        });

                        bsCollapse.show(); // Explicitly show the submenu
                    }
                }, 500); // Adjust delay to match sidebar transition duration
            }
        });
    });

    // Toggle button event with persistence
    if (sidebarToggle && !sidebarToggle.dataset.clickBound) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebarWithPersistence();
        });
        sidebarToggle.dataset.clickBound = "true";
    }

    // Add global listeners only once per page session
    if (!window.sidebarGlobalListenersAdded) {
        // Add click outside listener
        document.addEventListener("click", function (e) {
            // Ignore clicks that happened at or before the most recent turbo:load completed.
            if (e.timeStamp <= lastTurboLoadTime + 150) return;

            const currentSidebar = document.querySelector(".sidebar");
            const currentToggle = document.getElementById("sidebar-toggle");
            
            if (!currentSidebar) return;

            const isOpen = !currentSidebar.classList.contains("collapsed");

            if (
                isOpen &&
                !document.body.classList.contains("sidebar-locked") &&
                !currentSidebar.contains(e.target) &&
                !(currentToggle && currentToggle.contains(e.target))
            ) {
                localStorage.setItem("sidebarCollapsed", "true");
                
                // Fast closing
                currentSidebar.classList.add("sidebar-fast-close");
                const currentMain = document.querySelector(".main-content");
                if(currentMain) currentMain.classList.add("main-content-fast-close");
                
                currentSidebar.querySelectorAll(".nav-link-title").forEach((text) => text.classList.add("nav-text-fade-out"));
                
                setTimeout(() => {
                    currentSidebar.classList.add("collapsed");
                    if(currentMain) currentMain.classList.add("sidebar-collapsed");
                    document.body.classList.remove("sidebar-open");
                }, 50);
            }
        });

        // Enhanced responsive behavior with persistence
        let resizeTimeout;
        window.addEventListener("resize", function () {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const currentSidebar = document.querySelector(".sidebar");
                if (currentSidebar && window.innerWidth < 768) {
                    if (!currentSidebar.classList.contains("collapsed")) {
                        localStorage.setItem("sidebarCollapsed", "true");
                        currentSidebar.classList.add("collapsed");
                        document.body.classList.remove("sidebar-open");
                        const currentMain = document.querySelector(".main-content");
                        if(currentMain) currentMain.classList.add("sidebar-collapsed");
                    }
                }
            }, 150);
        });

        // Add keyboard support with persistence
        document.addEventListener("keydown", function (e) {
            // Toggle sidebar with Ctrl/Cmd + B
            if ((e.ctrlKey || e.metaKey) && e.key === "b") {
                const currentToggle = document.getElementById("sidebar-toggle");
                if (currentToggle) {
                    e.preventDefault();
                    currentToggle.click();
                }
            }
        });
        
        window.sidebarGlobalListenersAdded = true;
    }
}
