import { initEditWarehouseModal } from './partials/warehouse/edit/main.js';
import { initCreateWarehouseForm } from './partials/warehouse/create/main.js';
import { initializeSearch } from './partials/warehouse/search/main.js';

document.addEventListener("DOMContentLoaded", function () {
    initEditWarehouseModal();
    initCreateWarehouseForm();
    initializeSearch();
});
