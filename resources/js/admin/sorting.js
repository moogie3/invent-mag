import { initListJs } from './partials/sorting/listjs.js';
import { initSearch } from './partials/sorting/search.js';
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    initListJs();
    initSearch();
});