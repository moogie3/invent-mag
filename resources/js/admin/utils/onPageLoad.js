/**
 * onPageLoad - Turbo-compatible page initialization helper.
 *
 * Fires `callback` on the initial page load AND on every subsequent
 * Turbo navigation (turbo:load).
 *
 * Uses a namespace prefix to avoid collisions with other code.
 * Automatically resets the done flag on turbo:before-cache so cached
 * pages re-initialize correctly when restored.
 *
 * @param {Function} callback - The initialization function to run.
 * @param {Object}   options
 * @param {string}   options.id       - Unique identifier for this init block.
 * @param {boolean}  options.resetOnCache - Reset the done flag before Turbo caches.
 *                                          Pass false if you want the init to run
 *                                          only once per full page load (default: true).
 */
export function onPageLoad(callback, options = {}) {
    const {
        id = null,
        resetOnCache = true,
    } = options;

    const NS = '__onPageLoad__';

    const wrapper = () => {
        if (!id) {
            callback();
            return;
        }

        const done = `${NS}${id}`;
        if (window[done]) return;        // Already ran for this visit
        window[done] = true;
        callback();
    };

    // Listen for every Turbo page load
    document.addEventListener('turbo:load', wrapper);

    // Run immediately if DOM is already ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        wrapper();
    }

    // Before Turbo caches this page, wipe the done flag so the
    // cached version re-initializes properly when restored.
    if (id && resetOnCache) {
        const done = `${NS}${id}`;
        document.addEventListener('turbo:before-cache', () => {
            delete window[done];
        });
    }
}
