import { initListJs } from './partials/sorting/listjs.js';
import { initSearch } from './partials/sorting/search.js';
import { initServerSearch } from './partials/sorting/serverSearch.js';
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    initListJs();
    initSearch();
    // Pages marked with data-server-search on #searchInput swap in this
    // module instead; initSearch() bails out for them, so the two can never
    // both drive the same table.
    initServerSearch();
});