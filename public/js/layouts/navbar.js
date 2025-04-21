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
                                        .then((response) => response.json())
                                        .then((data) => {
                                            if (
                                                data.count === 0 &&
                                                notificationDot
                                            ) {
                                                notificationDot.remove();
                                            }
                                        });
                                }
                            });
                    }
                });
            });
        }
    };

    // Real-time notifications - Check for new notifications every 5 minutes
    const checkForNewNotifications = () => {
        fetch("/admin/notifications/count")
            .then((response) => response.json())
            .then((data) => {
                const notificationContainer = document.querySelector(
                    ".notification-dropdown"
                );
                const bellIcon =
                    document.querySelector("i.ti.ti-bell").parentNode;
                let notificationDot =
                    document.getElementById("notification-dot");

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
                            .then((response) => response.json())
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

        // Clear existing notifications (except header and footer)
        const header = container.querySelector(".dropdown-header");
        const footer =
            container.querySelector(".dropdown-divider")?.nextElementSibling;
        const divider = container.querySelector(".dropdown-divider");

        // Remove all children except header and footer
        while (container.firstChild) {
            if (
                container.firstChild !== header &&
                container.firstChild !== footer &&
                container.firstChild !== divider
            ) {
                container.removeChild(container.firstChild);
            } else {
                break;
            }
        }

        // Insert new notifications after header
        if (notifications.length > 0) {
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

                // Insert after header
                header.after(item);
            });
        } else {
            // No notifications message
            const emptyItem = document.createElement("div");
            emptyItem.className = "dropdown-item text-muted text-center";
            emptyItem.textContent = "No new notifications";
            header.after(emptyItem);
        }
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
