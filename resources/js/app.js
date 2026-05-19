// app.js - Main application entry point
// This runs once on initial page load

import "./bootstrap";

// Import Turbo
import * as Turbo from "@hotwired/turbo";
window.Turbo = Turbo;

// Import Alpine
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

// Admin-specific scripts are now conditionally loaded in script.blade.php
import "./admin/global-event-delegator.js";

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

