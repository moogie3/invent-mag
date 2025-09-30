import { initProductPage } from './partials/product/main.js';

import { initProductModal, loadExpiringSoonProductsModal } from './partials/product/modals/product.js'; // Import loadExpiringSoonProductsModal
import { initBulkSelection, clearProductSelection } from './partials/product/bulkActions/selection.js';
import { initSelectableTable } from "./layouts/selectable-table.js";

initProductPage();

document.addEventListener("DOMContentLoaded", function () {
    initSelectableTable();
    const pathname = window.location.pathname;

    if (pathname.includes("/admin/product/create")) {
        window.shortcutManager.register('ctrl+s', () => {
            document.querySelector('form').submit();
        }, 'Save Product');
    } else if (pathname.includes("/admin/product/edit")) {
        window.shortcutManager.register('ctrl+s', () => {
            document.querySelector('form').submit();
        }, 'Save Product');
    }

    window.shortcutManager.register('alt+n', () => {
        window.location.href = '/admin/product/create';
    }, 'New Product');
});
