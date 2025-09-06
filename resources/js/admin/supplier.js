import { initEditSupplierModal } from './partials/supplier/editModal/main.js';
import { initSrmSupplierModal } from './partials/supplier/srmModal/main.js';
import { initializeSearch } from './partials/supplier/search/main.js';

document.addEventListener("DOMContentLoaded", function () {
    initEditSupplierModal();
    initSrmSupplierModal();
    initializeSearch();
});