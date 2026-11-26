import { api, requireAuth } from '../api.js';
import { formatCurrency, formatNumber } from '../currency.js';
import { debounce, escapeHtml, initSheets, openSheet, setLoading, toast } from '../ui.js';

function resultRow(product) {
    const stock = Number(product.main_warehouse_stock || 0);
    const stockClass = stock > 0 ? 'badge-success' : 'badge-danger';

    return `
        <button type="button" class="result-row" data-product-id="${product.id}">
            <span class="list-main">
                <span class="list-title">${escapeHtml(product.name)}</span>
                <span class="list-sub">${escapeHtml(product.code)}${
                    product.category_name ? ` · ${escapeHtml(product.category_name)}` : ''
                }</span>
            </span>
            <span class="list-end">
                <span class="list-amount">${escapeHtml(
                    formatCurrency(product.selling_price),
                )}</span>
                <span class="badge ${stockClass}">${escapeHtml(
                    `${formatNumber(stock)} ${product.unit_symbol || ''}`,
                ).trim()}</span>
            </span>
        </button>`;
}

function renderResults(products) {
    const host = document.getElementById('product-results');
    const empty = document.getElementById('product-empty');
    if (!host) {
        return;
    }

    host.innerHTML = (products || []).map(resultRow).join('');
    if (empty) {
        empty.hidden = (products || []).length > 0;
    }
}

async function searchProducts(query) {
    const q = query.trim();
    if (!q) {
        renderResults([]);
        return;
    }

    setLoading('product-loading', true);
    try {
        const data = await api(`/api/v1/mobile/products/search?q=${encodeURIComponent(q)}`);
        renderResults(Array.isArray(data) ? data : []);
    } catch (err) {
        toast(err.message);
    } finally {
        setLoading('product-loading', false);
    }
}

/**
 * HID scanners type the whole barcode and press Enter. A miss here is not
 * fatal — the free-text search stays as-is so a partial code still works.
 */
async function lookupBarcode(raw) {
    const barcode = String(raw || '').trim();
    if (!barcode) {
        return;
    }

    setLoading('product-loading', true);
    try {
        const product = await api(
            `/api/v1/mobile/products/barcode?barcode=${encodeURIComponent(barcode)}`,
        );
        await showDetail(product);
    } catch (err) {
        if (err.status === 404) {
            toast(`No product with barcode ${barcode}`);
        } else {
            toast(err.message);
        }
    } finally {
        setLoading('product-loading', false);
    }
}

async function showDetail(product) {
    const body = document.getElementById('detail-body');
    if (body) {
        body.innerHTML = `<div class="spinner" role="status"></div>`;
    }
    openSheet('detail');

    // Re-fetch so the sheet always shows full stock across warehouses, not
    // just the main-warehouse figure the search rows carry.
    let full = product;
    try {
        full = await api(`/api/v1/mobile/products/${product.id}`);
    } catch {
        // Fall back to the row we already have rather than blanking the sheet.
    }

    if (!body) {
        return;
    }

    const rows = [
        ['Code', full.code],
        ['Barcode', full.barcode],
        ['Category', full.category_name],
        ['Unit', full.unit_symbol],
        ['Selling price', formatCurrency(full.selling_price)],
        ['Cost price', formatCurrency(full.price)],
        ['Main warehouse stock', formatNumber(full.main_warehouse_stock)],
        ['Total stock', formatNumber(full.total_stock)],
        ['Low stock threshold', formatNumber(full.low_stock_threshold)],
    ].filter(([, value]) => value !== null && value !== undefined && value !== '');

    body.innerHTML = `
        <div class="list-title" style="font-size:17px;margin-bottom:10px;">
            ${escapeHtml(full.name)}
        </div>
        <dl class="detail-grid">
            ${rows
                .map(
                    ([label, value]) => `
                        <dt>${escapeHtml(label)}</dt>
                        <dd>${escapeHtml(value)}</dd>`,
                )
                .join('')}
        </dl>
        ${
            full.description
                ? `<p class="hint" style="margin-top:12px;">${escapeHtml(full.description)}</p>`
                : ''
        }`;
}

export function initProductsPage() {
    if (!requireAuth()) {
        return;
    }

    initSheets();

    const search = document.getElementById('product-search');
    if (search) {
        search.addEventListener(
            'input',
            debounce(() => searchProducts(search.value), 300),
        );
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchProducts(search.value);
            }
        });
    }

    const barcode = document.getElementById('product-barcode-input');
    if (barcode) {
        barcode.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            lookupBarcode(barcode.value);
            barcode.value = '';
        });
    }

    const scanButton = document.getElementById('product-scan-button');
    if (scanButton) {
        scanButton.addEventListener('click', () => barcode && barcode.focus());
    }

    const results = document.getElementById('product-results');
    if (results) {
        results.addEventListener('click', (event) => {
            const row = event.target.closest('[data-product-id]');
            if (!row) {
                return;
            }
            const id = Number(row.dataset.productId);
            const known = results.querySelector(`[data-product-id="${id}"]`);
            if (known) {
                showDetail({
                    id,
                    name: known.querySelector('.list-title')?.textContent || '',
                    code: known.querySelector('.list-sub')?.textContent || '',
                });
            }
        });
    }
}
