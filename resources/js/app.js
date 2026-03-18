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
