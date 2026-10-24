import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { datasheetUrl } from '@/js/admin/partials/product/utils/datasheet.js';
import { renderSearchResults } from '@/js/admin/partials/product/search/ui.js';

const semiconductor = (overrides = {}) => ({
    id: 1,
    code: 'TRS-0001',
    name: 'Transistor TIP122 Darlington',
    price: 1000,
    selling_price: 2000,
    stock_quantity: 5,
    low_stock_threshold: 10,
    image: '',
    category: { name: 'TRS' },
    unit: { symbol: 'PCS' },
    supplier: { name: 'Supplier A' },
    ...overrides,
});

describe('resources/js/admin/partials/product/utils/datasheet.js', () => {
    it('builds the alldatasheet search URL from the part in the name', () => {
        expect(datasheetUrl('Transistor TIP122 Darlington', 'TRS')).toBe(
            'https://www.alldatasheet.com/view.jsp?Searchword=TIP122',
        );
        expect(datasheetUrl('IC TDA4510 TV Jungle', 'Integrated Circuit (IC)')).toBe(
            'https://www.alldatasheet.com/view.jsp?Searchword=TDA4510',
        );
        expect(datasheetUrl('Dioda Rectifier 1N4007', 'dIO ')).toBe(
            'https://www.alldatasheet.com/view.jsp?Searchword=1N4007',
        );
        expect(datasheetUrl('MOSFET IRF540N', 'MOS')).toBe(
            'https://www.alldatasheet.com/view.jsp?Searchword=IRF540N',
        );
    });

    it('returns null outside semiconductor categories or without a part number', () => {
        expect(datasheetUrl('Transistor TIP122', 'CAP')).toBeNull();
        expect(datasheetUrl('Kapasitor Elco 470uf', 'Capacitor')).toBeNull();
        expect(datasheetUrl('Transistor NPN Power Analog', 'TRS')).toBeNull();
        expect(datasheetUrl('', 'IC')).toBeNull();
        expect(datasheetUrl('TIP122', undefined)).toBeNull();
    });
});

describe('resources/js/admin/partials/product/search/ui.js datasheet item', () => {
    beforeEach(() => {
        window.currencySettings = {
            currency_code: 'IDR',
            decimal_places: 0,
            decimal_separator: ',',
            thousand_separator: '.',
            currency_symbol: 'Rp',
            position: 'prefix',
        };
        document.body.innerHTML = `
            <input type="checkbox" id="selectAll" class="row-checkbox">
            <div id="bulkActionsBar" style="display:none"></div>
            <table><tbody></tbody></table>`;
    });

    afterEach(() => {
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    it('adds a Datasheet dropdown item for semiconductor rows', () => {
        renderSearchResults([semiconductor()]);

        const link = document.querySelector('a[href*="alldatasheet.com"]');
        expect(link).not.toBeNull();
        expect(link.getAttribute('href')).toBe(
            'https://www.alldatasheet.com/view.jsp?Searchword=TIP122',
        );
        expect(link.getAttribute('target')).toBe('_blank');
        expect(link.getAttribute('rel')).toContain('noopener');
        expect(link.textContent).toContain('Datasheet');

        const items = Array.from(document.querySelectorAll('.dropdown-item')).map((a) => a.textContent.trim());
        expect(items.indexOf('Datasheet')).toBeGreaterThan(items.indexOf('View'));
    });

    it('omits the item for non-semiconductor rows', () => {
        renderSearchResults([
            semiconductor({
                id: 2,
                code: 'CAP-0001',
                name: 'Kapasitor Elco 470uf TIP122',
                category: { name: 'CAP' },
            }),
        ]);

        expect(document.querySelector('a[href*="alldatasheet"]')).toBeNull();
    });
});
