/**
 * Navbar functionality
 * Handles notifications, navigation hover effects, and mobile menu
 */
document.addEventListener("DOMContentLoaded", function () {
    // Initialize Bootstrap components
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Remember the active notification tab
    function handleTabClick(e) {
        const tabId = e.target.id;
        localStorage.setItem("activeNotificationTab", tabId);
        e.stopPropagation(); // Prevent event from bubbling up to close the dropdown
    }

    // Apply click handlers to all notification tabs
    const notificationTabs = document.querySelectorAll(
        "#notificationTabs .nav-link"
    );
    notificationTabs.forEach((tab) => {
        tab.addEventListener("click", handleTabClick);
    });

    // Set the active tab when dropdown is shown
    const notificationBell = document.getElementById("notification-bell");
    if (notificationBell) {
        notificationBell.addEventListener("click", function () {
            // Delay slightly to ensure dropdown is rendered
            setTimeout(() => {
                const savedTab = localStorage.getItem("activeNotificationTab");
                if (savedTab) {
                    const tabToActivate = document.getElementById(savedTab);
                    if (tabToActivate) {
                        // Create and dispatch a click event
                        const clickEvent = new Event("click");
                        tabToActivate.dispatchEvent(clickEvent);
                    }
                }
            }, 100);
        });
    }

    // Notification dot handling
    const initNotifications = () => {
        const notificationItems =
            document.querySelectorAll(".notification-item");
        const notificationDot = document.getElementById("notification-dot");

        if (notificationItems.length > 0) {
            notificationItems.forEach((item) => {
                item.addEventListener("click", function (e) {
                    const notificationId = this.getAttribute(
                        "data-notification-id"
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
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute("content"),
                                },
                            }
                        )
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success && notificationDot) {
                                    // If this was the last notification, remove the dot
                                    fetch("/admin/notifications/count")
                                        .then((response) => {
                                            if (!response.ok) {
                                                throw new Error(
                                                    `Network response was not ok: ${response.status}`
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
                                            console.error(
                                                "Error checking notification count:",
                                                error
                                            );
                                        });
                                }
                            })
                            .catch((error) => {
                                console.error(
                                    "Error marking notification as read:",
                                    error
                                );
                            });
                    }
                });
            });
        }
    };

    // Real-time notifications - Check for new notifications every 5 minutes
    const checkForNewNotifications = () => {
        fetch("/admin/notifications/count")
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        `Network response was not ok: ${response.status}`
                    );
                }
                return response.json();
            })
            .then((data) => {
                const notificationContainer = document.querySelector(
                    ".notification-dropdown"
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
                                    throw new Error(
                                        `Network response was not ok: ${response.status}`
                                    );
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
                                        data.notifications
                                    );
                                }
                            })
                            .catch((error) => {
                                console.error(
                                    "Error fetching notification list:",
                                    error
                                );
                            });
                    }
                } else if (data.count === 0 && notificationDot) {
                    // Remove the dot if no notifications
                    notificationDot.style.display = "none";
                }
            })
            .catch((error) => {
                console.error("Error checking notifications:", error);
            });
    };

    // Function to update notification dropdown content
    const updateNotificationDropdown = (notifications) => {
        // Function implementation remains the same
        // ...
    };

    // Navigation hover functionality
    const initNavHover = () => {
        const brandTrigger = document.getElementById("brand-trigger");
        const navContainer = document.querySelector(".nav-container");
        const overlay = document.getElementById("nav-overlay");
        const dropdown = document.getElementById("nav-dropdown");
        let timeout;

        if (!brandTrigger || !navContainer || !dropdown) return;

        // Show dropdown on hover
        brandTrigger.addEventListener("mouseenter", function () {
            clearTimeout(timeout);
            navContainer.classList.add("active");
        });

        // Hide dropdown when mouse leaves
        navContainer.addEventListener("mouseleave", function () {
            timeout = setTimeout(() => {
                navContainer.classList.remove("active");
            }, 300);
        });

        // Prevent dropdown from closing when hovering inside it
        dropdown.addEventListener("mouseenter", function () {
            clearTimeout(timeout);
        });

        dropdown.addEventListener("mouseleave", function () {
            timeout = setTimeout(() => {
                navContainer.classList.remove("active");
            }, 300);
        });

        // Close dropdown when clicking overlay
        if (overlay) {
            overlay.addEventListener("click", function () {
                navContainer.classList.remove("active");
            });
        }
    };

    // FIX: Improved Mobile navigation functionality
    const initMobileNav = () => {
        const mobileMenuToggle = document.getElementById("mobile-menu-toggle");
        const mobileNav = document.getElementById("mobile-nav");
        const overlay = document.getElementById("nav-overlay");

        if (!mobileMenuToggle || !mobileNav) {
            console.error("Mobile menu elements not found");
            return;
        }

        // FIX: Use touchstart event for mobile to improve responsiveness
        ["click", "touchstart"].forEach((eventType) => {
            mobileMenuToggle.addEventListener(eventType, function (event) {
                event.preventDefault(); // Prevent default behavior
                event.stopPropagation(); // Stop event from propagating

                
                mobileNav.classList.toggle("active");

                // Also toggle the overlay for better UX
                if (overlay) {
                    if (mobileNav.classList.contains("active")) {
                        overlay.style.visibility = "visible";
                        overlay.style.opacity = "1";
                    } else {
                        overlay.style.visibility = "hidden";
                        overlay.style.opacity = "0";
                    }
                }
            });
        });

        // Close menu when clicking on the overlay
        if (overlay) {
            overlay.addEventListener("click", function () {
                mobileNav.classList.remove("active");
                overlay.style.visibility = "hidden";
                overlay.style.opacity = "0";
            });
        }

        // Close menu when clicking outside
        document.addEventListener("click", function (event) {
            if (
                mobileNav.classList.contains("active") &&
                !mobileNav.contains(event.target) &&
                !mobileMenuToggle.contains(event.target)
            ) {
                mobileNav.classList.remove("active");
                if (overlay) {
                    overlay.style.visibility = "hidden";
                    overlay.style.opacity = "0";
                }
            }
        });

        // FIX: Add touch event support for better mobile experience
        document.addEventListener("touchstart", function (event) {
            if (
                mobileNav.classList.contains("active") &&
                !mobileNav.contains(event.target) &&
                !mobileMenuToggle.contains(event.target) &&
                event.target !== overlay
            ) {
                mobileNav.classList.remove("active");
                if (overlay) {
                    overlay.style.visibility = "hidden";
                    overlay.style.opacity = "0";
                }
            }
        });

        // FIX: Make each nav item close the menu when clicked
        const mobileNavItems = mobileNav.querySelectorAll("a");
        mobileNavItems.forEach((item) => {
            item.addEventListener("click", function () {
                // Close mobile menu after selecting an item
                // (unless it's a dropdown trigger)
                if (!this.classList.contains("dropdown-toggle")) {
                    mobileNav.classList.remove("active");
                    if (overlay) {
                        overlay.style.visibility = "hidden";
                        overlay.style.opacity = "0";
                    }
                }
            });
        });
    };

    // Fix for notification tabs to prevent dropdown from closing when clicking tabs
    const fixNotificationTabsDropdown = () => {
        const notificationTabs = document.querySelectorAll(
            ".notification-tabs .nav-link"
        );
        notificationTabs.forEach((tab) => {
            tab.addEventListener("click", function (e) {
                e.stopPropagation(); // Prevent event from bubbling up
            });
        });
    };

    // Initialize all navbar functionality
    initNotifications();
    initNavHover();
    initMobileNav();
    fixNotificationTabsDropdown();

    // Check for notifications immediately
    checkForNewNotifications();

    // Check for new notifications every 5 minutes
    setInterval(checkForNewNotifications, 5 * 60 * 1000);

    // Optional: Uncomment to enable more frequent notification count updates
    // setInterval(updateNotificationCount, 60000); // Check every minute
});
