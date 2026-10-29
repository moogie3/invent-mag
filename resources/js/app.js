// app.js - Main application entry point
// This runs once on initial page load

import "./bootstrap";

// Import Turbo
import * as Turbo from "@hotwired/turbo";
window.Turbo = Turbo;

// Some admin routes (settings, users, HR) intentionally render outside the
// main turbo-frame to avoid modal conflicts (see admin/layouts/base.blade.php).
// When a link inside the frame targets one of those routes, the response has
// no matching frame and Turbo would show "Content missing". Fall back to a
// full page visit instead — https://turbo.hotwired.dev/handbook/frames#handling-missing-frames
document.addEventListener("turbo:frame-missing", function (event) {
    event.preventDefault();
    event.detail.visit(event.detail.response);
});

// Import Alpine
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

// Admin-specific scripts are now conditionally loaded in script.blade.php
import "./admin/global-event-delegator.js";

// Initialize global form validation
import { initFormValidation } from "./admin/utils/formValidator.js";
initFormValidation();

// Read currency settings from body data attribute
document.addEventListener('turbo:load', function() {
    var body = document.body;
    if (body && body.hasAttribute('data-currency-settings')) {
        try {
            window.currencySettings = JSON.parse(body.getAttribute('data-currency-settings'));
        } catch (e) {
            console.error('Failed to parse currency settings', e);
        }
    }
});
// Run once on initial load too
if (document.body && document.body.hasAttribute('data-currency-settings')) {
    try {
        window.currencySettings = JSON.parse(document.body.getAttribute('data-currency-settings'));
    } catch (e) {
        console.error('Failed to parse currency settings', e);
    }
}

