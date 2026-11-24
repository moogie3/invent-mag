import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { initShopeeImport } from '@/js/admin/partials/product/shopeeImport.js';

// The image phase polls per-import media counters until they settle, but a
// 5-minute cap stops the polling early. When nothing failed yet, that cap must
// report "still downloading", never a failure.
const DONE_LABEL = 'All :count cover images imported';
const PARTIAL_LABEL = ':done of :total cover images imported (:failed failed)';
const PENDING_LABEL =
    ':done of :total cover images imported, the rest continue in the background';

function buildDom() {
    document.body.innerHTML = `
        <form id="shopeeImportForm"
              action="/admin/products/shopee-import"
              data-progress-url="/admin/products/shopee-import/progress"
              data-label-media-done="${DONE_LABEL}"
              data-label-media-partial="${PARTIAL_LABEL}"
              data-label-media-pending="${PENDING_LABEL}">
            <input type="hidden" id="shopeeImportProgressToken" name="progress_token">
            <button type="submit" id="shopeeImportSubmitBtn">Import</button>
            <div id="shopeeImportProgress" style="display:none">
                <div id="shopeeImportProgressBar"></div>
                <span id="shopeeImportProgressPct"></span>
                <span id="shopeeImportProgressLabel"></span>
            </div>
            <div id="shopeeImportResult"></div>
        </form>
        <div id="shopeeImportModal"></div>`;
}

function stubFetch() {
    vi.spyOn(window, 'fetch').mockImplementation((url, options = {}) => {
        if (options.method === 'POST') {
            return Promise.resolve({
                ok: true,
                status: 200,
                json: () =>
                    Promise.resolve({
                        success: true,
                        message: 'Imported',
                        summary: { images_queued: 50 },
                    }),
            });
        }
        // Media counters: five images cached, nothing failed, 45 still queued.
        return Promise.resolve({
            ok: true,
            status: 200,
            json: () =>
                Promise.resolve({ media: { total: 50, done: 5, failed: 0 } }),
        });
    });
}

async function flushMicrotasks() {
    for (let i = 0; i < 30; i++) {
        await vi.advanceTimersByTimeAsync(0);
    }
}

async function submitImport() {
    const form = document.getElementById('shopeeImportForm');
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    await flushMicrotasks();
}

describe('shopee import media phase cap', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        buildDom();
        stubFetch();
        globalThis.InventMagApp = { showToast: vi.fn() };
        initShopeeImport();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
        document.body.innerHTML = '';
        delete globalThis.InventMagApp;
    });

    it('does not claim a failure at the cap when nothing has failed', async () => {
        await submitImport();

        // 5-minute cap fires while 45 images are still downloading.
        await vi.advanceTimersByTimeAsync(300000);

        const line = document.querySelector(
            '#shopeeImportResult .media-result-line',
        );
        expect(line).toBeTruthy();

        expect(line.classList.contains('text-warning')).toBe(false);
        expect(line.textContent).not.toMatch(/0 failed/);
        expect(line.textContent).toContain(
            'the rest continue in the background',
        );
    });

    it('keeps the warning copy at the cap when items really failed', async () => {
        window.fetch.mockImplementation((url, options = {}) => {
            if (options.method === 'POST') {
                return Promise.resolve({
                    ok: true,
                    status: 200,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            message: 'Imported',
                            summary: { images_queued: 50 },
                        }),
                });
            }
            return Promise.resolve({
                ok: true,
                status: 200,
                json: () =>
                    Promise.resolve({
                        media: { total: 50, done: 5, failed: 2 },
                    }),
            });
        });

        await submitImport();
        await vi.advanceTimersByTimeAsync(300000);

        const line = document.querySelector(
            '#shopeeImportResult .media-result-line',
        );
        expect(line).toBeTruthy();
        expect(line.classList.contains('text-warning')).toBe(true);
        expect(line.textContent).toContain('2 failed');
    });
});
