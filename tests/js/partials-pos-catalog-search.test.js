import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { initCatalogSearch } from '@/js/admin/partials/pos/catalog/search.js';

const GRID_DATASETS = `
    data-search-url="/admin/pos/products"
    data-limit="60"
    data-has-more="1"
    data-load-more-label="Load more"
    data-empty-title="No products found"
    data-empty-subtitle="No products yet"
    data-in-stock-label="In Stock"`;

function setupDom({ serverCards = 1 } = {}) {
    const cards = Array.from({ length: serverCards }, (_, i) => `
        <div class="col-md-4 mb-2">
            <div class="card product-card" data-product-id="${i + 1}" data-product-name="Server ${i + 1}">
                <div class="card-body"><h5 class="card-title fs-4 mb-1 product-card-title">Server ${i + 1}</h5></div>
            </div>
        </div>`).join('');

    document.body.innerHTML = `
        <input type="text" id="searchProduct" />
        <div class="pos-catalog-scroll">
            <div class="row g-2" id="productGrid" ${GRID_DATASETS}>${cards}</div>
        </div>`;
}

function mockFetchWith(products, has_more) {
    return vi.spyOn(window, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, products, has_more }),
    });
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

const scroller = () => document.querySelector('.pos-catalog-scroll');
const grid = () => document.getElementById('productGrid');

const searchResult = (overrides = {}) => ({
    id: 7,
    code: 'DIN-1',
    name: 'Dinamo Kipas Angin',
    selling_price: 85000,
    unit_symbol: 'PCS',
    main_warehouse_stock: 98,
    image: 'https://cf.shopee.co.id/file/cover.jpg',
    is_placeholder: false,
    ...overrides,
});

describe('resources/js/admin/partials/pos/catalog/search.js', () => {
    beforeEach(() => {
        window.currencySettings = {
            currency_code: 'IDR',
            decimal_places: 0,
            decimal_separator: ',',
            thousand_separator: '.',
            currency_symbol: 'Rp',
            position: 'prefix',
        };
        setupDom();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    it('exports initCatalogSearch', () => {
        expect(typeof initCatalogSearch).toBe('function');
    });

    it('replaces the grid with server search results after the debounce', async () => {
        vi.useFakeTimers();
        const fetchMock = mockFetchWith(
            [searchResult(), searchResult({ id: 8, name: 'No Image Yet', image: '/img/default_placeholder.png', is_placeholder: true })],
            true,
        );
        initCatalogSearch();

        const input = document.getElementById('searchProduct');
        input.value = 'dinamo';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await vi.advanceTimersByTimeAsync(300);

        expect(fetchMock).toHaveBeenCalledWith(
            '/admin/pos/products?q=dinamo&offset=0',
            expect.objectContaining({ headers: expect.objectContaining({ Accept: 'application/json' }) }),
        );

        const g = grid();
        expect(g.querySelectorAll('.product-card')).toHaveLength(2);
        expect(g.textContent).not.toContain('Server 1'); // old cards replaced

        const card = g.querySelector('[data-product-id="7"]');
        expect(card.getAttribute('data-product-name')).toBe('Dinamo Kipas Angin');
        expect(card.getAttribute('data-product-stock')).toBe('98');
        expect(card.querySelector('h5').textContent).toBe('Dinamo Kipas Angin');
        expect(card.querySelector('img').getAttribute('src')).toBe('https://cf.shopee.co.id/file/cover.jpg');
        expect(card.querySelector('img').getAttribute('loading')).toBe('lazy');
        expect(card.querySelector('.product-stock-display').classList.contains('bg-success')).toBe(true);

        const placeholderCard = g.querySelector('[data-product-id="8"]');
        expect(placeholderCard.querySelector('img')).toBeNull();
        expect(placeholderCard.querySelector('i.ti-photo')).not.toBeNull();

        expect(g.dataset.hasMore).toBe('1'); // has_more keeps scroll-loading armed
    });

    it('shows the localized empty state when nothing matches', async () => {
        vi.useFakeTimers();
        mockFetchWith([], false);
        initCatalogSearch();

        const input = document.getElementById('searchProduct');
        input.value = 'zzz';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await vi.advanceTimersByTimeAsync(300);

        const g = grid();
        expect(g.querySelector('.empty-title').textContent).toBe('No products found');
        expect(g.querySelectorAll('.product-card')).toHaveLength(0);
        expect(g.dataset.hasMore).toBe('0');
        expect(g.querySelector('button')).toBeNull();
    });

    it('appends the next page when the wrapper is scrolled to the bottom', async () => {
        const fetchMock = mockFetchWith([searchResult({ id: 9, name: 'Next Page Item' })], false);
        initCatalogSearch();

        scroller().dispatchEvent(new Event('scroll'));
        await flush();

        expect(fetchMock).toHaveBeenCalledWith(
            '/admin/pos/products?q=&offset=1', // offset = the 1 server-rendered card
            expect.anything(),
        );

        const g = grid();
        expect(g.querySelectorAll('.product-card')).toHaveLength(2);
        expect(g.textContent).toContain('Server 1'); // appended, not replaced
        expect(g.textContent).toContain('Next Page Item');
        expect(g.dataset.hasMore).toBe('0');
        expect(g.querySelector('.spinner-border')).toBeNull(); // loading row cleaned up
        expect(g.querySelector('button')).toBeNull();
    });

    it('does not fetch more on scroll once the catalog is fully loaded', async () => {
        const fetchMock = mockFetchWith([], false);
        grid().dataset.hasMore = '0'; // captured by init as the starting state
        initCatalogSearch();

        scroller().dispatchEvent(new Event('scroll'));
        await flush();

        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('offers a retry button when an append request fails', async () => {
        const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const fetchMock = vi.spyOn(window, 'fetch')
            .mockRejectedValueOnce(new Error('network down'))
            .mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    has_more: false,
                    products: [searchResult({ id: 11, name: 'Recovered Item' })],
                }),
            });
        initCatalogSearch();

        scroller().dispatchEvent(new Event('scroll'));
        await flush();

        const retry = grid().querySelector('button.btn-outline-primary');
        expect(retry).not.toBeNull();
        expect(retry.textContent).toBe('Load more'); // data-load-more-label
        expect(grid().querySelectorAll('.product-card')).toHaveLength(1); // server cards kept

        retry.click();
        await flush();

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(grid().textContent).toContain('Recovered Item');
        expect(grid().querySelector('button')).toBeNull(); // success clears the retry
        expect(errorSpy).toHaveBeenCalled();
    });

    it('ignores a stale search response that a newer keystroke superseded', async () => {
        vi.useFakeTimers();
        const fetchSpy = vi.spyOn(window, 'fetch').mockImplementation((url) => {
            const slow = String(url).includes('dil');
            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve({
                        ok: true,
                        json: () => Promise.resolve({
                            success: true,
                            has_more: false,
                            products: [searchResult({ name: slow ? 'Slow Result' : 'Fast Result' })],
                        }),
                    });
                }, slow ? 500 : 50);
            });
        });
        initCatalogSearch();

        const input = document.getElementById('searchProduct');
        input.value = 'dil';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await vi.advanceTimersByTimeAsync(260); // first request starts (slow)

        input.value = 'din';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await vi.advanceTimersByTimeAsync(800); // both resolve; fast one is latest

        expect(fetchSpy).toHaveBeenCalledTimes(2);
        expect(grid().textContent).toContain('Fast Result');
        expect(grid().textContent).not.toContain('Slow Result');
    });
});
