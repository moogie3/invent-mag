/**
 * onPageLoad - Turbo-compatible page initialization helper.
 *
 * Fires `callback` on the initial page load AND on every subsequent
 * Turbo navigation (turbo:load), without double-firing on first load.
 *
 * Usage:
 *   import { onPageLoad } from '../utils/onPageLoad.js';
 *   onPageLoad(() => { ... });
 */
export function onPageLoad(callback) {
    // Fire on initial load (DOMContentLoaded or immediately if already past it)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        // Already loaded (e.g. script is deferred and runs after DOM is ready)
        callback();
    }

    // Fire on every Turbo navigation AFTER the initial page load.
    // turbo:load also fires on the first page visit, but by then
    // DOMContentLoaded has already run our callback above, so we
    // track whether we've seen the first turbo:load and skip it.
    let firstTurboLoad = true;
    document.addEventListener('turbo:load', function () {
        if (firstTurboLoad) {
            firstTurboLoad = false;
            return;
        }
        callback();
    });
}
