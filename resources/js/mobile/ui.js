/**
 * DOM helpers shared by the mobile PWA pages: escaping, debouncing,
 * toast notifications, and the bottom-sheet open/close protocol.
 */

/** Escape untrusted text before it goes near innerHTML. */
export function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/** Trailing-edge debounce for search inputs. */
export function debounce(fn, delay = 300) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

let toastTimer = null;

/**
 * Transient message above the tab bar. One toast at a time — a second call
 * replaces the first rather than stacking unreadable pills on a phone.
 */
export function toast(message, duration = 2600) {
    const host = document.getElementById('toast-host');
    if (!host) {
        return;
    }

    host.innerHTML = `<div class="toast">${escapeHtml(message)}</div>`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        host.innerHTML = '';
    }, duration);
}

function sheetParts(name) {
    return {
        sheet: document.getElementById(`${name}-sheet`),
        overlay: document.getElementById(`${name}-overlay`),
    };
}

export function openSheet(name) {
    const { sheet, overlay } = sheetParts(name);
    if (!sheet || !overlay) {
        return;
    }
    overlay.classList.add('open');
    sheet.classList.add('open');
    document.body.style.overflow = 'hidden';
}

export function closeSheet(name) {
    const { sheet, overlay } = sheetParts(name);
    if (!sheet || !overlay) {
        return;
    }
    overlay.classList.remove('open');
    sheet.classList.remove('open');
    document.body.style.overflow = '';
}

export function closeAllSheets() {
    ['cart', 'pay', 'receipt', 'detail'].forEach(closeSheet);
}

/** Wire overlay taps, [data-close-sheet] buttons and Escape for one page. */
export function initSheets() {
    document.querySelectorAll('[data-close-sheet]').forEach((button) => {
        button.addEventListener('click', () => closeSheet(button.dataset.closeSheet));
    });

    document.querySelectorAll('.sheet-overlay').forEach((overlay) => {
        overlay.addEventListener('click', () => {
            const name = overlay.id.replace(/-overlay$/, '');
            closeSheet(name);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllSheets();
        }
    });
}

/** Show/hide a loading spinner and a result container as a pair. */
export function setLoading(spinnerId, loading) {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        spinner.hidden = !loading;
    }
}
