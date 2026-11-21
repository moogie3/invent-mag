import { describe, it, expect, vi } from 'vitest';
import { onPageLoad } from '@/js/admin/utils/onPageLoad.js';

/**
 * onPageLoad() is how every admin entry point boots. The `id` option is the
 * only thing standing between "run once" and "run again on every event", so
 * these tests pin that behaviour down.
 *
 * The sales pipeline calls it with no options at all, which is why pressing
 * F5 on that page fired the product fetch twice.
 */
describe('resources/js/admin/utils/onPageLoad.js', () => {
    it('runs immediately on a full page load', () => {
        const callback = vi.fn();

        onPageLoad(callback);

        expect(document.readyState).toBe('complete');
        expect(callback).toHaveBeenCalledTimes(1);
    });

    it('without an id, runs AGAIN on every turbo:load (no dedup)', () => {
        const callback = vi.fn();

        onPageLoad(callback);

        // One from the initial load, one from each turbo:load after it.
        document.dispatchEvent(new Event('turbo:load'));
        document.dispatchEvent(new Event('turbo:load'));

        expect(callback).toHaveBeenCalledTimes(3);
    });

    it('with an id, runs exactly once no matter how many turbo:loads fire', () => {
        const callback = vi.fn();

        onPageLoad(callback, { id: 'unique-init-block' });

        document.dispatchEvent(new Event('turbo:load'));
        document.dispatchEvent(new Event('turbo:load'));
        document.dispatchEvent(new Event('turbo:load'));

        expect(callback).toHaveBeenCalledTimes(1);
    });

    it('re-arms a cached page when resetOnCache is left at its default', () => {
        const callback = vi.fn();

        onPageLoad(callback, { id: 'cached-init-block' });

        // Turbo caches the page, then restores it: the done flag is wiped so
        // the restored copy re-initialises.
        document.dispatchEvent(new Event('turbo:before-cache'));
        document.dispatchEvent(new Event('turbo:load'));

        expect(callback).toHaveBeenCalledTimes(2);
    });
});
