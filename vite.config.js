import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/auth.css",
                "resources/js/app.js",
                "resources/js/admin/auth.js",
                "resources/js/admin/category.js",
                "resources/js/admin/currency.js",
                "resources/js/admin/customer.js",
                "resources/js/admin/pos.js",
                "resources/js/admin/product.js",
                "resources/js/admin/profile.js",
                "resources/js/admin/purchase-order.js",
                "resources/js/admin/recentts.js",
                "resources/js/admin/sales-order.js",
                "resources/js/admin/sorting.js",
                "resources/js/admin/supplier.js",
                "resources/js/admin/unit.js",
                "resources/js/admin/user.js",
                "resources/js/admin/warehouse.js",
                "resources/js/admin/helpers/toast.js",
                "resources/js/admin/layouts/modal.js",
                "resources/js/admin/layouts/page-loader.js",
                "resources/css/navbar.css",
                "resources/js/admin/layouts/navbar.js",
                "resources/js/admin/sales-pipeline.js",
                "resources/js/admin/settings.js",
                "resources/css/menu-sidebar.css",
                "resources/js/menu-sidebar.js",
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
        jsx: false,
    },
});
