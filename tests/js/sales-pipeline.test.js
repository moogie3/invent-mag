import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Imported for its side effects: registering the onPageLoad callback and the
// turbo:before-cache reset. The callback returns immediately on import
// because the URL is not the pipeline page yet, so nothing runs until the
// test dispatches turbo:load itself.
import '@/js/admin/sales-pipeline.js';

const PRODUCT_SEARCH = '/admin/product/search';

// onPageLoad is given the id 'sales-pipeline', so it keeps this done flag on
// window. It is set as soon as the module is imported, which is why each test
// has to drop it: a real F5 gets a brand new window, and without dropping it
// the callback would never be reached at all.
const ON_PAGE_LOAD_DONE = '__onPageLoad__sales-pipeline';

function productFetches() {
    return window.fetch.mock.calls.filter(([url]) =>
        String(url).includes(PRODUCT_SEARCH)
    ).length;
}

const pageMarkup = `
    <div class="card-body"
         data-initial-pipelines=""
         data-initial-customers=""></div>
    <select id="pipelineSelect"></select>
    <input id="opportunityExpectedCloseDate">
    <input id="editOpportunityExpectedCloseDate">
`;

function tick() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

describe('resources/js/admin/sales-pipeline.js boot guard', () => {
    beforeEach(() => {
        delete window[ON_PAGE_LOAD_DONE];
        window.history.pushState({}, '', '/admin/sales-pipeline');
        document.body.innerHTML = pageMarkup;
        window.fetch.mockClear();
        window.InventMagApp = { showToast: vi.fn() };
        // Registered by a shared script in production; the callback reaches
        // it at the very end of the boot, so the test has to supply it too.
        window.shortcutManager = { register: vi.fn() };
    });

    afterEach(() => {
        window.history.pushState({}, '', '/');
        document.body.innerHTML = '';
        delete window.InventMagApp;
        delete window.shortcutManager;
    });

    it('boots once even when the callback is reached twice', async () => {
        document.dispatchEvent(new Event('turbo:load'));
        await tick();

        expect(productFetches()).toBe(1);

        // Drop onPageLoad's done flag so the callback really is reached a
        // second time. That is the situation the marker on the page anchor
        // exists to survive, and without it this second load would fire a
        // second product fetch and rebind every listener in the module.
        delete window[ON_PAGE_LOAD_DONE];
        document.dispatchEvent(new Event('turbo:load'));
        await tick();

        expect(productFetches()).toBe(1);
    });

    it('boots again after the page is restored from the Turbo cache', async () => {
        document.dispatchEvent(new Event('turbo:load'));
        await tick();
        expect(productFetches()).toBe(1);

        // Turbo snapshots the page, marker and all. Clearing it on
        // before-cache is what stops a restored copy coming back up blank.
        document.dispatchEvent(new Event('turbo:before-cache'));
        document.dispatchEvent(new Event('turbo:load'));
        await tick();

        expect(productFetches()).toBe(2);
    });

    it('boots again on a fresh visit, because a new page carries a new anchor', async () => {
        document.dispatchEvent(new Event('turbo:load'));
        await tick();
        expect(productFetches()).toBe(1);

        // A fresh visit brings both halves of a fresh page load: a new window,
        // so onPageLoad's done flag is gone, and a new body, so the anchor
        // (and its boot marker) is a brand new element.
        delete window[ON_PAGE_LOAD_DONE];
        document.body.innerHTML = pageMarkup;

        document.dispatchEvent(new Event('turbo:load'));
        await tick();

        expect(productFetches()).toBe(2);
    });
});
