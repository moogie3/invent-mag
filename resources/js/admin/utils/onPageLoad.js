/**
 * onPageLoad - Turbo-compatible page initialization helper.
 *
 * Fires `callback` on the initial page load AND on every subsequent
 * Turbo navigation (turbo:load), without double-firing on first load.
 */
export function onPageLoad(callback) {
    let fired = false;

    const wrapper = (event) => {
        if (fired) return;
        fired = true;
        callback(event);
        
        // Reset for next Turbo navigation
        setTimeout(() => { fired = false; }, 300);
    };

    // Standard Turbo event
    document.addEventListener('turbo:load', wrapper);
    
    // Fallback for initial load if turbo:load already fired or script is deferred
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        // Use a small delay to allow turbo:load a chance to fire first
        setTimeout(() => {
            if (!fired) wrapper({ type: 'initial' });
        }, 10);
    }
}
