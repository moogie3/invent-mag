/**
 * Enhanced Navbar functionality
 * Handles notifications, navigation hover effects, avatar hover dropdown, and mobile menu
 */
document.addEventListener("DOMContentLoaded", function () {
    // Initialize Bootstrap components
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Avatar dropdown hover functionality
    const initAvatarHoverDropdown = () => {
        const avatarDropdowns = document.querySelectorAll(".nav-item.dropdown");

        avatarDropdowns.forEach((dropdown) => {
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
                                        '[data-bs-toggle="dropdown"]'
                                    )
                                    ?.setAttribute("aria-expanded", "false");
                            }
                        }
                    });

                // Show current dropdown
                dropdownMenu.classList.add("show");
                this.querySelector('[data-bs-toggle="dropdown"]')?.setAttribute(
                    "aria-expanded",
                    "true"
                );
            });

            // Hide dropdown when mouse leaves
            dropdown.addEventListener("mouseleave", function () {
                hoverTimeout = setTimeout(() => {
                    dropdownMenu.classList.remove("show");
                    this.querySelector(
                        '[data-bs-toggle="dropdown"]'
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

            // Prevent default Bootstrap click behavior for avatar dropdown
            const dropdownToggle = dropdown.querySelector(
                '[data-bs-toggle="dropdown"]'
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
        "#notificationTabs .nav-link"
    );
    notificationTabs.forEach((tab) => {
        tab.addEventListener("click", handleTabClick);
    });

    // Set the active tab when dropdown is shown
    const notificationBell = document.getElementById("notification-bell");
    if (notificationBell) {
        // For click functionality (mobile)
        notificationBell.addEventListener("click", function (e) {
            // Only handle click on mobile devices
            if (window.innerWidth < 768) {
                setTimeout(() => {
                    const savedTab = localStorage.getItem(
                        "activeNotificationTab"
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
                        "activeNotificationTab"
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
        // Implementation for updating notification content
        // This would depend on your specific notification structure
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

    // Enhanced icon hover effects
    const initIconHoverEffects = () => {
        // Bell icon special animation
        const bellIcon = document.querySelector(".ti-bell");
        if (bellIcon) {
            const navLink = bellIcon.closest(".nav-link");
            if (navLink) {
                navLink.addEventListener("mouseenter", function () {
                    bellIcon.style.transform = "scale(1.1)";
                    bellIcon.style.transition = "transform 0.2s ease-in-out";
                });

                navLink.addEventListener("mouseleave", function () {
                    bellIcon.style.transform = "scale(1)";
                });
            }
        }

        // Theme toggle icons rotation effect
        const themeIcons = document.querySelectorAll(".ti-moon, .ti-sun");
        themeIcons.forEach((icon) => {
            const navLink = icon.closest(".nav-link");
            if (navLink) {
                navLink.addEventListener("mouseenter", function () {
                    icon.style.transform = "scale(1.1) rotate(15deg)";
                    icon.style.transition = "transform 0.3s ease";
                });

                navLink.addEventListener("mouseleave", function () {
                    icon.style.transform = "scale(1) rotate(0deg)";
                });
            }
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

    // Close dropdowns when clicking outside (for better UX)
    const initOutsideClickHandler = () => {
        document.addEventListener("click", function (e) {
            // Close all hover dropdowns when clicking outside
            if (!e.target.closest(".nav-item.dropdown")) {
                document
                    .querySelectorAll(".nav-item.dropdown .dropdown-menu.show")
                    .forEach((menu) => {
                        menu.classList.remove("show");
                        const dropdown = menu.closest(".dropdown");
                        if (dropdown) {
                            dropdown
                                .querySelector('[data-bs-toggle="dropdown"]')
                                ?.setAttribute("aria-expanded", "false");
                        }
                    });
            }
        });
    };

    // Mobile responsive adjustments
    const initResponsiveBehavior = () => {
        const handleResize = () => {
            // Re-enable click behavior on mobile
            if (window.innerWidth < 768) {
                // Remove hover effects on mobile
                document
                    .querySelectorAll(".nav-item.dropdown")
                    .forEach((dropdown) => {
                        dropdown.style.pointerEvents = "auto";
                    });
            }
        };

        window.addEventListener("resize", handleResize);
        handleResize(); // Initial call
    };

    // Initialize all navbar functionality
    initAvatarHoverDropdown();
    initNotifications();
    initNavHover();
    initIconHoverEffects();
    fixNotificationTabsDropdown();
    initOutsideClickHandler();
    initResponsiveBehavior();

    // Check for notifications immediately
    checkForNewNotifications();

    // Check for new notifications every 5 minutes
    setInterval(checkForNewNotifications, 5 * 60 * 1000);
});
