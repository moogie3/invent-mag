import { formatCurrency } from '../../utils/currencyFormatter.js';

/**
 * Live preview of the tax reversal a return will book (PPN / PPh).
 *
 * The figures are fetched from the server, which derives them with the same
 * code path the journal and the show page use (deriveReversal()), so this
 * module never reimplements the arithmetic and the preview cannot drift from
 * the GL. It only decides what to show.
 */
export class TaxReversalPreview {
    constructor(element) {
        this.element = element;
        this.timer = null;

        if (!this.element) {
            return;
        }

        this.url = this.element.dataset.deriveUrl;
        this.lines = {
            pph: this.element.querySelector('[data-line="pph"]'),
            ppn: this.element.querySelector('[data-line="ppn"]'),
            share: this.element.querySelector('[data-line="share"]'),
        };
    }

    /**
     * Refresh the preview for the current parent document and return total.
     * Hides itself while there is nothing to derive (no document selected,
     * or a zero total), and debounces the request because quantity inputs
     * fire on every keystroke.
     */
    update(idParam, idValue, totalAmount) {
        if (!this.element || !this.url) {
            return;
        }

        clearTimeout(this.timer);

        if (!idValue || !(totalAmount > 0)) {
            this.element.hidden = true;
            return;
        }

        this.timer = setTimeout(() => {
            const query = `${idParam}=${encodeURIComponent(idValue)}&total=${encodeURIComponent(totalAmount)}`;

            fetch(`${this.url}?${query}`)
                .then(response => (response.ok ? response.json() : Promise.reject(new Error('derive failed'))))
                .then(data => this.render(data))
                .catch(() => {
                    // A preview is advisory: on any failure the block simply
                    // stays hidden rather than showing a stale figure.
                    this.element.hidden = true;
                });
        }, 300);
    }

    render(data) {
        const ppn = Number(data.ppn || 0);
        const pph = Number(data.pph || 0);
        const ratio = Number(data.ratio || 0);

        this.lines.ppn.hidden = !(ppn > 0);
        this.lines.pph.hidden = !(pph > 0);
        this.lines.ppn.querySelector('[data-amount]').textContent = formatCurrency(ppn);
        this.lines.pph.querySelector('[data-amount]').textContent = formatCurrency(pph);

        const isPartial = ratio > 0 && ratio < 1;
        this.lines.share.hidden = !isPartial;
        if (isPartial) {
            const percent = Math.round(ratio * 1000) / 10;
            this.lines.share.textContent = this.lines.share.dataset.template.replace(':percent', percent);
        }

        this.element.hidden = !(ppn > 0 || pph > 0);
    }
}
