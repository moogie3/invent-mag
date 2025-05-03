/**
 * Navbar functionality
 * Handles notifications, navigation hover effects, and mobile menu
 */
document.addEventListener("DOMContentLoaded", function () {
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

                    // Optional: Mark notification as read via AJAX
                    if (notificationId) {
                        fetch(
                            `/admin/notifications/mark-read/${notificationId}`,
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
                                                notificationDot.remove();
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
                    notificationDot.remove();
                }
            })
            .catch((error) => {
                console.error("Error checking notifications:", error);
            });
    };

    // Function to update notification dropdown content
    const updateNotificationDropdown = (notifications) => {
        const container = document.querySelector(".notification-dropdown");
        if (!container) return;

        // Find the header, footer and divider elements
        const header = container.querySelector(".dropdown-header");
        const divider = container.querySelector(".dropdown-divider");
        const footer = divider ? divider.nextElementSibling : null;

        if (!header) return; // Exit if header not found

        // First, remove all existing notification items (but keep header, divider, and footer)
        const itemsToRemove = [];
        container.childNodes.forEach((node) => {
            if (node !== header && node !== divider && node !== footer) {
                itemsToRemove.push(node);
            }
        });

        // Remove the identified notification items
        itemsToRemove.forEach((node) => node.remove());

        // Now add the new notifications after the header
        if (notifications.length > 0) {
            // Create a document fragment to improve performance
            const fragment = document.createDocumentFragment();

            notifications.forEach((notification) => {
                const item = document.createElement("a");
                item.href = notification.route;
                item.className =
                    "dropdown-item d-flex align-items-center notification-item";
                item.setAttribute("data-notification-id", notification.id);

                const urgencyClass =
                    notification.urgency === "high"
                        ? "danger"
                        : notification.urgency === "medium"
                        ? "warning"
                        : "info";

                item.innerHTML = `
                    <span class="badge bg-${urgencyClass} me-2"></span>
                    <div>
                        <strong>${notification.title}</strong>
                        <div class="text-muted small">${notification.description}</div>
                    </div>
                `;

                fragment.appendChild(item);
            });

            // Insert all notifications after header at once
            if (divider) {
                container.insertBefore(fragment, divider);
            } else {
                container.appendChild(fragment);
            }
        } else {
            // No notifications message
            const emptyItem = document.createElement("div");
            emptyItem.className = "dropdown-item text-muted text-center";
            emptyItem.textContent = "No new notifications";

            if (divider) {
                container.insertBefore(emptyItem, divider);
            } else {
                container.appendChild(emptyItem);
            }
        }

        // Reinitialize notification click handlers
        initNotifications();
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

    // Mobile navigation functionality
    const initMobileNav = () => {
        const mobileMenuToggle = document.getElementById("mobile-menu-toggle");
        const mobileNav = document.getElementById("mobile-nav");

        if (!mobileMenuToggle || !mobileNav) return;

        mobileMenuToggle.addEventListener("click", function () {
            mobileNav.classList.toggle("active");
        });

        // Close menu when clicking outside
        document.addEventListener("click", function (event) {
            if (
                mobileNav.classList.contains("active") &&
                !mobileNav.contains(event.target) &&
                !mobileMenuToggle.contains(event.target)
            ) {
                mobileNav.classList.remove("active");
            }
        });
    };

    // Initialize all navbar functionality
    initNotifications();
    initNavHover();
    initMobileNav();

    // Check for notifications immediately
    checkForNewNotifications();

    // Check for new notifications every 5 minutes
    setInterval(checkForNewNotifications, 5 * 60 * 1000);
});
