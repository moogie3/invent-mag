/// <reference types="vitest" />
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";

export default defineConfig({
    test: {
        globals: true,
        environment: "jsdom",
        setupFiles: "tests/js/setup.js",
    },
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/auth.css",
                "resources/js/app.js",
                "resources/js/admin/auth.js",
                "resources/js/admin/accounting.js",
                "resources/js/admin/adjustment-log.js",
                "resources/js/admin/category.js",
                "resources/js/admin/journal-entry.js",
                "resources/js/admin/currency.js",
                "resources/js/admin/customer.js",
                "resources/js/admin/pos.js",
                "resources/js/admin/product.js",
                "resources/js/admin/profile.js",
                "resources/js/admin/reset-password.js",
                "resources/js/admin/purchase-order.js",
                "resources/js/admin/purchase-return.js",
                "resources/js/admin/sales-return.js",
                "resources/js/admin/recentts.js",
                "resources/js/admin/sales-order.js",
                "resources/js/admin/sorting.js",
                "resources/js/admin/supplier.js",
                "resources/js/admin/unit.js",
                "resources/js/admin/user.js",
                "resources/js/admin/warehouse.js",
                "resources/js/admin/helpers/notification.js",
                "resources/js/admin/layouts/page-loader.js",
                "resources/css/navbar.css",
                "resources/js/admin/layouts/navbar.js",
                "resources/js/admin/sales-pipeline.js",
                "resources/js/admin/layouts/settings.js",
                "resources/css/menu-sidebar.css",
                "resources/js/admin/layouts/menu-sidebar.js",
                "resources/js/admin/layouts/theme-toggle.js",
                "resources/js/admin/layouts/theme-visibility.js",
                "resources/js/admin/layouts/theme-initializer.js",
                "resources/js/admin/utils/sound.js",
                "resources/js/admin/layouts/advanced-settings.js",
                "resources/js/admin/layouts/global-keyboard-shortcuts.js",
                "resources/js/admin/layouts/selectable-table.js",
                "resources/js/admin/tax.js",
                "resources/css/pos-receipt.css",
                "resources/css/error.css",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "resources"),
        },
    },
    optimizeDeps: {
        exclude: [],
    },
    esbuild: {
        target: "es2015", // Transpile to ES2015 for broader browser compatibility
    },
    server: {
        cors: {
            origin: '*'
        },
        hmr: {
            host: 'localhost',
        }
    },
});
