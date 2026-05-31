/**
 * Enhanced Navbar functionality
 * Handles notifications, navigation hover effects, avatar hover dropdown, and mobile menu
 */
import { onPageLoad } from "../utils/onPageLoad.js";

onPageLoad(function () {
    // Initialize Bootstrap tooltips
    if (window.bootstrap) {
        var tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.bs-tooltip-bound)'),
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            try {
                tooltipTriggerEl.classList.add('bs-tooltip-bound');
                return new bootstrap.Tooltip(tooltipTriggerEl);
            } catch(e) {}
        });
    }

    // Avatar dropdown hover functionality
    const initAvatarHoverDropdown = () => {
        const avatarDropdowns = document.querySelectorAll("#avatar-dropdown:not(.avatar-bound)");

        avatarDropdowns.forEach((dropdown) => {
            dropdown.classList.add("avatar-bound");
            const dropdownMenu = dropdown.querySelector(".dropdown-menu");
            let hoverTimeout;

            if (!dropdownMenu) return;

            // Show dropdown on hover
            dropdown.addEventListener("mouseenter", function () {
                clearTimeout(hoverTimeout);

                // Close any other open dropdowns first
                document
                    .querySelectorAll(".dropdown-menu.show")
                    .forEach((menu) => {
                        if (menu !== dropdownMenu) {
                            menu.classList.remove("show");
                            const otherDropdown = menu.closest(".dropdown");
                            if (otherDropdown) {
                                otherDropdown
                                    .querySelector(
                                        '[data-bs-toggle="dropdown"]',
                                    )
                                    ?.setAttribute("aria-expanded", "false");
                            }
                        }
                    });

                // Show current dropdown
                dropdownMenu.classList.add("show");
                this.querySelector('[data-bs-toggle="dropdown"]')?.setAttribute(
                    "aria-expanded",
                    "true",
                );
            });

            // Hide dropdown when mouse leaves
            dropdown.addEventListener("mouseleave", function () {
                hoverTimeout = setTimeout(() => {
                    dropdownMenu.classList.remove("show");
                    this.querySelector(
                        '[data-bs-toggle="dropdown"]',
                    )?.setAttribute("aria-expanded", "false");
                }, 300); // 300ms delay before hiding
            });

            // Keep dropdown open when hovering over the dropdown menu itself
            dropdownMenu.addEventListener("mouseenter", function () {
                clearTimeout(hoverTimeout);
            });

            dropdownMenu.addEventListener("mouseleave", function () {
                hoverTimeout = setTimeout(() => {
                    this.classList.remove("show");
                    dropdown
                        .querySelector('[data-bs-toggle="dropdown"]')
                        ?.setAttribute("aria-expanded", "false");
                }, 300);
            });

            // Prevent default Bootstrap click behavior for avatar dropdown on desktop
            const dropdownToggle = dropdown.querySelector(
                '[data-bs-toggle="dropdown"]',
            );
            if (dropdownToggle) {
                dropdownToggle.addEventListener("click", function (e) {
                    // Only prevent default if we're not on mobile
                    if (window.innerWidth >= 768) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            }
        });
    };

    // Remember the active notification tab
    function handleTabClick(e) {
        const tabId = e.target.id;
        localStorage.setItem("activeNotificationTab", tabId);
        e.stopPropagation(); // Prevent event from bubbling up to close the dropdown
    }

    // Apply click handlers to all notification tabs
    const notificationTabs = document.querySelectorAll(
        "#notificationTabs .nav-link:not(.tab-bound)",
    );
    notificationTabs.forEach((tab) => {
        tab.classList.add("tab-bound");
        tab.addEventListener("click", handleTabClick);
    });

    // Set the active tab when dropdown is shown
    const notificationBell = document.getElementById("notification-bell");
    if (notificationBell && !notificationBell.dataset.bound) {
        notificationBell.dataset.bound = "true";
        // For click functionality (mobile)
        notificationBell.addEventListener("click", function (e) {
            // Only handle click on mobile devices
            if (window.innerWidth < 768) {
                setTimeout(() => {
                    const savedTab = localStorage.getItem(
                        "activeNotificationTab",
                    );
                    if (savedTab) {
                        const tabToActivate = document.getElementById(savedTab);
                        if (tabToActivate) {
                            const clickEvent = new Event("click");
                            tabToActivate.dispatchEvent(clickEvent);
                        }
                    }
                }, 100);
            } else {
                // Prevent default Bootstrap behavior on desktop (we handle with hover)
                e.preventDefault();
                e.stopPropagation();
            }
        });

        // For hover functionality (desktop)
        notificationBell.addEventListener("mouseenter", function () {
            if (window.innerWidth >= 768) {
                setTimeout(() => {
                    const savedTab = localStorage.getItem(
                        "activeNotificationTab",
                    );
                    if (savedTab) {
                        const tabToActivate = document.getElementById(savedTab);
                        if (tabToActivate) {
                            const clickEvent = new Event("click");
                            tabToActivate.dispatchEvent(clickEvent);
                        }
                    }
                }, 100);
            }
        });
    }

    // Notification dot handling
    const initNotifications = () => {
        const notificationItems =
            document.querySelectorAll(".notification-item:not(.item-bound)");
        const notificationDot = document.getElementById("notification-dot");

        if (notificationItems.length > 0) {
            notificationItems.forEach((item) => {
                item.classList.add("item-bound");
                item.addEventListener("click", function (e) {
                    const notificationId = this.getAttribute(
                        "data-notification-id",
                    );

                    // Mark notification as read via AJAX
                    if (notificationId) {
                        fetch(
                            `/admin/notifications/mark-as-read/${notificationId}`,
                            {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": document
                                        .querySelector(
                                            'meta[name="csrf-token"]',
                                        )
                                        .getAttribute("content"),
                                },
                            },
                        )
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success && notificationDot) {
                                    // If this was the last notification, remove the dot
                                    fetch("/admin/notifications/count")
                                        .then((response) => {
                                            if (!response.ok) {
                                                throw new Error(
                                                    `Network response was not ok: ${response.status}`,
                                                );
                                            }
                                            return response.json();
                                        })
                                        .then((data) => {
                                            if (
                                                data.count === 0 &&
                                                notificationDot
                                            ) {
                                                notificationDot.style.display =
                                                    "none";
                                            }
                                        })
                                        .catch((error) => {
                                        });
                                }
                            })
                            .catch((error) => {
                            });
                    }
                });
            });
        }
    };

    // Real-time notifications - Check for new notifications every 5 minutes
    const checkForNewNotifications = () => {
        // CRITICAL: Only run if the bell icon exists (authenticated UI)
        if (!document.getElementById('notification-bell')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
        if (!csrfToken) return;

        fetch("/admin/notifications/count")
            .then((response) => {
                if (!response.ok) {
                    // Silently handle errors - don't break the UI
                    return { json: () => ({ count: 0 }) };
                }
                return response.json();
            })
            .then((data) => {
                if (!data || typeof data.count === 'undefined') return;
                
                const notificationContainer = document.querySelector(
                    ".notification-dropdown",
                );
                const bellIcon =
                    document.querySelector("i.ti.ti-bell")?.parentNode;
                let notificationDot =
                    document.getElementById("notification-dot");

                if (!bellIcon) return; // Exit if bell icon not found

                // If there are notifications but no dot, add the dot
                if (data.count > 0) {
                    if (!notificationDot) {
                        const dot = document.createElement("span");
                        dot.id = "notification-dot";
                        dot.className =
                            "position-absolute bg-danger border border-light rounded-circle";
                        bellIcon.appendChild(dot);
                    } else {
                        notificationDot.style.display = "block";
                    }

                    // Optional: Refresh the notification list
                    if (notificationContainer) {
                        fetch("/admin/notifications/list")
                            .then((response) => {
                                if (!response.ok) {
                                    return { json: () => ({ notifications: [] }) };
                                }
                                return response.json();
                            })
                            .then((data) => {
                                // Update notification dropdown content
                                if (
                                    data.notifications &&
                                    data.notifications.length > 0
                                ) {
                                    updateNotificationDropdown(
                                        data.notifications,
                                    );
                                }
                            })
                            .catch((error) => {
                            });
                    }
                } else if (data.count === 0 && notificationDot) {
                    // Remove the dot if no notifications
                    notificationDot.style.display = "none";
                }
            })
            .catch((error) => {
            });
    };

    // Function to update notification dropdown content
    const updateNotificationDropdown = (notifications) => {
        // Implementation for updating notification content
    };

    // Navigation hover functionality
    const initNavHover = () => {
        const brandTrigger = document.getElementById("brand-trigger");
        const navContainer = document.querySelector(".nav-container");
        const navDropdown = document.getElementById("nav-dropdown");
        const overlay = document.getElementById("nav-overlay");
        let hoverTimeout;

        if (!brandTrigger || !navContainer || !navDropdown) return;
        
        if (brandTrigger.dataset.bound) return;
        brandTrigger.dataset.bound = "true";

        // Show dropdown on hover of brand trigger (icon only)
        brandTrigger.addEventListener("mouseenter", function () {
            clearTimeout(hoverTimeout);
            navContainer.classList.add("active"); // Show the main dropdown container
            navDropdown.classList.add("collapsed"); // Collapse menu to icons
        });

        // Hide dropdown and reset when mouse leaves nav-container
        navContainer.addEventListener("mouseleave", function () {
            hoverTimeout = setTimeout(() => {
                navContainer.classList.remove("active");
                navDropdown.classList.remove("collapsed");
            }, 300);
        });

        // Prevent dropdown from closing when hovering inside it (if not collapsed)
        navDropdown.addEventListener("mouseenter", function () {
            clearTimeout(hoverTimeout);
        });

        // Close dropdown when clicking overlay
        if (overlay) {
            overlay.addEventListener("click", function () {
                navContainer.classList.remove("active");
                navDropdown.classList.remove("collapsed");
            });
        }
    };

    // Staggered dropdown children animation
    const initStaggeredDropdown = () => {
        document
            .querySelectorAll(".nav-item.dropdown > .nav-link.dropdown-toggle:not(.stagger-bound)")
            .forEach((toggle) => {
                toggle.classList.add("stagger-bound");
                const dropdown = toggle.closest(".nav-item.dropdown");
                
                // Add hybrid animation listeners
                if (dropdown && (dropdown.id === "reports-nav-item" || dropdown.id === "accounting-nav-item" || dropdown.id === "administration-nav-item")) {
                    if (!dropdown.dataset.animationInitialized) {
                        dropdown.addEventListener("shown.bs.dropdown", function () {
                            const dropdownMenu = this.querySelector(".dropdown-menu");
                            const dropdownItems = dropdownMenu.querySelectorAll(".dropdown-item");
                            dropdownMenu.classList.add("staggered-open");
                            dropdownItems.forEach((item, index) => {
                                item.style.animationDelay = `${index * 0.05}s`;
                            });
                        });
                        dropdown.addEventListener("hide.bs.dropdown", function () {
                            const dropdownMenu = this.querySelector(".dropdown-menu");
                            const dropdownItems = dropdownMenu.querySelectorAll(".dropdown-item");
                            dropdownMenu.classList.remove("staggered-open");
                            dropdownItems.forEach((item) => {
                                item.style.animationDelay = "";
                            });
                        });
                        dropdown.dataset.animationInitialized = "true";
                    }
                    return;
                }

                toggle.addEventListener("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdownMenu = this.closest(".dropdown").querySelector(".dropdown-menu");
                    if (!dropdownMenu) return;

                    dropdownMenu.classList.toggle("show");
                    this.setAttribute("aria-expanded", dropdownMenu.classList.contains("show"));

                    const dropdownItems = dropdownMenu.querySelectorAll(".dropdown-item");

                    if (dropdownMenu.classList.contains("show")) {
                        dropdownMenu.classList.add("staggered-open");
                        dropdownItems.forEach((item, index) => {
                            item.style.animationDelay = `${index * 0.05}s`;
                        });
                    } else {
                        dropdownMenu.classList.remove("staggered-open");
                        dropdownItems.forEach((item) => {
                            item.style.animationDelay = "";
                        });
                    }
                });
            });
    };

    // Fix for notification tabs to prevent dropdown from closing when clicking tabs
    const fixNotificationTabsDropdown = () => {
        const notificationTabs = document.querySelectorAll(".notification-tabs .nav-link:not(.fix-bound)");
        notificationTabs.forEach((tab) => {
            tab.classList.add("fix-bound");
            tab.addEventListener("click", function (e) {
                e.stopPropagation();
            });
        });
    };

    // Close dropdowns when clicking outside (for better UX)
    const initOutsideClickHandler = () => {
        if (window.navbarOutsideClickBound) return;
        window.navbarOutsideClickBound = true;
        
        document.addEventListener("click", function (e) {
            if (!e.target.closest(".nav-item.dropdown") && !e.target.closest("#nav-dropdown")) {
                document.querySelectorAll(".nav-item.dropdown .dropdown-menu.show").forEach((menu) => {
                    menu.classList.remove("show");
                    const dropdown = menu.closest(".dropdown");
                    if (dropdown) {
                        dropdown.querySelector('[data-bs-toggle="dropdown"]')?.setAttribute("aria-expanded", "false");
                    }
                });
                const navContainer = document.querySelector(".nav-container");
                const navDropdown = document.getElementById("nav-dropdown");
                if (navContainer?.classList.contains("active") && !e.target.closest("#nav-dropdown")) {
                    navContainer.classList.remove("active");
                    navDropdown.classList.remove("collapsed");
                }
            }
        });
    };

    // Mobile responsive adjustments
    const initResponsiveBehavior = () => {
        if (window.navbarResponsiveBound) return;
        window.navbarResponsiveBound = true;
        
        const handleResize = () => {
            if (window.innerWidth < 768) {
                document.querySelectorAll(".nav-item.dropdown").forEach((dropdown) => {
                    dropdown.style.pointerEvents = "auto";
                });
            }
        };
        window.addEventListener("resize", handleResize);
        handleResize();
    };

    // Initialize all navbar functionality
    initAvatarHoverDropdown();
    initNotifications();
    initNavHover();
    initStaggeredDropdown();
    fixNotificationTabsDropdown();
    
    // Global window/document listeners should only be added once
    initOutsideClickHandler();
    initResponsiveBehavior();
    
    // Attach to dynamic DOM elements safely
    if (!window.navbarScrollBound) {
        window.addEventListener("scroll", function () {
            const navbar = document.querySelector(".navbar");
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add("scrolled");
                } else {
                    navbar.classList.remove("scrolled");
                }
            }
        });
        window.navbarScrollBound = true;
    }

    const sidebarToggle = document.getElementById("sidebar-toggle");
    if (sidebarToggle && !sidebarToggle.dataset.navBound) {
        sidebarToggle.dataset.navBound = "true";
        sidebarToggle.addEventListener("click", function () {
            document.body.classList.toggle("sidebar-open");
        });
    }

    // Handle navbar sidebar toggle for layout-navbar mode
    const navbarSidebarToggle = document.getElementById("navbar-sidebar-toggle");
    if (navbarSidebarToggle && !navbarSidebarToggle.dataset.navBound) {
        navbarSidebarToggle.dataset.navBound = "true";
        navbarSidebarToggle.addEventListener("click", function () {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('sidebar-toggled');
                document.body.classList.toggle('sidebar-open');
                document.body.classList.toggle('sidebar-toggled-open');
            }
        });
    }

    // Only start the notification interval if the bell exists
    if (!window.notificationIntervalStarted && document.getElementById('notification-bell')) {
        setInterval(checkForNewNotifications, 5 * 60 * 1000);
        window.notificationIntervalStarted = true;
    }

    // Only check for notifications if the bell icon exists
    if (document.getElementById('notification-bell')) {
        checkForNewNotifications();
    }
});