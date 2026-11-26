import { api, requireAuth } from '../api.js';
import { formatCurrency } from '../currency.js';
import { computeTotals } from '../totals.js';
import {
    debounce,
    escapeHtml,
    initSheets,
    openSheet,
    closeSheet,
    setLoading,
    toast,
} from '../ui.js';

const PAGE_SIZE = 60;
const CART_KEY = 'm_pos_cart';

/** @type {{id:number, code:string, name:string, price:number, quantity:number, unit:string, stock:number}[]} */
let cart = [];
/** @type {{id:number, name:string, rate:number, type:string, is_pph:boolean}[]} */
let taxes = [];
let defaultTaxId = null;
let catalog = [];
let offset = 0;
let hasMore = false;
let searchQuery = '';
let paymentMethod = 'Cash';

// ---------------------------------------------------------------- cart state

function loadCart() {
    try {
        const stored = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        cart = Array.isArray(stored) ? stored : [];
    } catch {
        cart = [];
    }
}

function saveCart() {
    try {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
    } catch {
        // Storage full or blocked — the in-memory cart still works this session.
    }
}

function cartSubtotal() {
    return cart.reduce((sum, line) => sum + line.price * line.quantity, 0);
}

function selectedTax() {
    const id = Number(document.getElementById('cart-tax')?.value || 0);
    return taxes.find((tax) => tax.id === id) || null;
}

function selectedPph() {
    const id = Number(document.getElementById('cart-pph')?.value || 0);
    return taxes.find((tax) => tax.id === id) || null;
}

/**
 * Reads the discount / tax / PPh controls off the cart sheet and runs the
 * shared arithmetic. Returns the figures both the sheet and the payment
 * sheet display, plus the raw inputs the API needs.
 */
function readTotals() {
    const discountValue = parseFloat(document.getElementById('cart-discount')?.value || 0);
    const discountType = document.getElementById('cart-discount-type')?.value || 'fixed';
    const tax = selectedTax();
    const pph = selectedPph();

    const totals = computeTotals({
        subtotal: cartSubtotal(),
        discountValue: Number.isFinite(discountValue) ? discountValue : 0,
        discountType,
        taxRate: tax ? Number(tax.rate) : 0,
        taxType: tax ? tax.type : 'none',
        pphRate: pph ? Number(pph.rate) : 0,
    });

    return { ...totals, discountValue, discountType, tax, pph };
}

function renderCart() {
    const lines = document.getElementById('cart-lines');
    const emptyNote = document.getElementById('cart-empty-note');
    const totalsBox = document.getElementById('cart-totals');
    const bar = document.getElementById('cart-bar');
    const barCount = document.getElementById('cart-count');
    const barTotal = document.getElementById('cart-bar-total');
    const checkout = document.getElementById('cart-checkout');

    const count = cart.reduce((sum, line) => sum + line.quantity, 0);
    if (barCount) {
        barCount.textContent = String(count);
    }
    if (bar) {
        bar.hidden = cart.length === 0;
    }

    if (cart.length === 0) {
        if (lines) {
            lines.innerHTML = '';
        }
        if (emptyNote) {
            emptyNote.hidden = false;
        }
        if (totalsBox) {
            totalsBox.hidden = true;
        }
        if (checkout) {
            checkout.disabled = true;
        }
        saveCart();
        return;
    }

    if (emptyNote) {
        emptyNote.hidden = true;
    }
    if (totalsBox) {
        totalsBox.hidden = false;
    }
    if (checkout) {
        checkout.disabled = false;
    }

    if (lines) {
        lines.innerHTML = cart
            .map(
                (line) => `
                <div class="cart-line" data-line-id="${line.id}">
                    <div class="cart-line-main">
                        <div class="cart-line-name">${escapeHtml(line.name)}</div>
                        <div class="cart-line-price">${escapeHtml(
                            formatCurrency(line.price),
                        )} &times; ${escapeHtml(line.unit || '')}</div>
                    </div>
                    <div class="qty">
                        <button type="button" data-qty="-1" aria-label="Decrease">−</button>
                        <span>${line.quantity}</span>
                        <button type="button" data-qty="1" aria-label="Increase">+</button>
                    </div>
                    <div class="cart-line-total">${escapeHtml(
                        formatCurrency(line.price * line.quantity),
                    )}</div>
                </div>`,
            )
            .join('');
    }

    renderTotals();
    saveCart();
}

/** Mirrors totals.js: same elements, same order, same rounding. */
function renderTotals() {
    const { taxAmount, pphAmount, grandTotal, tax, pph } = readTotals();

    const set = (id, text) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = text;
        }
    };

    set('cart-subtotal', formatCurrency(cartSubtotal()));
    set('cart-tax-amount', formatCurrency(taxAmount));
    set('cart-pph-amount', formatCurrency(pphAmount));
    set('cart-grand-total', formatCurrency(grandTotal));

    const taxRow = document.getElementById('cart-tax-row');
    if (taxRow) {
        taxRow.hidden = !tax;
    }
    const taxLabel = document.getElementById('cart-tax-label');
    if (taxLabel && tax) {
        taxLabel.textContent = `Tax (${tax.name} ${tax.rate}%)`;
    }

    const pphRow = document.getElementById('cart-pph-row');
    if (pphRow) {
        pphRow.hidden = !pph;
    }

    // The payment sheet repeats the grand total, so keep it in step.
    set('pay-grand-total', formatCurrency(grandTotal));
    updateChange();

    const barTotal = document.getElementById('cart-bar-total');
    if (barTotal) {
        barTotal.textContent = formatCurrency(grandTotal);
    }
}

// ------------------------------------------------------------------- catalog

function productCard(product) {
    const out = Number(product.main_warehouse_stock || 0) <= 0;
    const stockLabel = out
        ? `<span class="prod-stock out">Out</span>`
        : `<span class="prod-stock ok">${escapeHtml(
              `${product.main_warehouse_stock} ${product.unit_symbol || ''}`,
          ).trim()}</span>`;

    const image = product.image && !product.is_placeholder
        ? `<img src="${escapeHtml(product.image)}" alt="" style="width:100%;height:72px;object-fit:cover;border-radius:8px;margin-bottom:6px;" loading="lazy" />`
        : '';

    return `
        <button type="button" class="prod-card" data-product-id="${product.id}">
            ${image}
            <span class="prod-name">${escapeHtml(product.name)}</span>
            <span class="prod-code">${escapeHtml(product.code)}</span>
            <span class="row-between" style="margin-top:auto;">
                <span class="prod-price">${escapeHtml(formatCurrency(product.selling_price))}</span>
                ${stockLabel}
            </span>
        </button>`;
}

async function loadCatalog({ append = false } = {}) {
    const grid = document.getElementById('pos-catalog');
    const empty = document.getElementById('pos-catalog-empty');
    const loadMore = document.getElementById('pos-load-more');

    if (!append) {
        offset = 0;
        setLoading('pos-catalog-loading', true);
    }

    try {
        const params = new URLSearchParams({
            q: searchQuery,
            offset: String(append ? offset : 0),
        });
        const data = await api(`/api/v1/mobile/pos/products?${params.toString()}`);

        const products = data.products || [];
        catalog = append ? catalog.concat(products) : products;
        offset = catalog.length;
        hasMore = Boolean(data.has_more);

        if (grid) {
            grid.innerHTML = catalog.map(productCard).join('');
        }
        if (empty) {
            empty.hidden = catalog.length > 0;
        }
        if (loadMore) {
            loadMore.hidden = !hasMore;
        }
    } catch (err) {
        toast(err.message);
    } finally {
        setLoading('pos-catalog-loading', false);
    }
}

function addToCart(product) {
    const existing = cart.find((line) => line.id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            code: product.code,
            name: product.name,
            price: Number(product.selling_price),
            quantity: 1,
            unit: product.unit_symbol || '',
            stock: Number(product.main_warehouse_stock || 0),
        });
    }
    renderCart();
    toast(`${product.name} added`);
}

// -------------------------------------------------------------------- taxes

async function loadTaxes() {
    try {
        const data = await api('/api/v1/mobile/pos/taxes');
        taxes = data.taxes || [];
        defaultTaxId = data.default_tax_id || null;
    } catch {
        // Without the tax list the cart still sells at 0%; the server applies
        // its own default only when tax_id is absent, so we always send it.
        taxes = [];
        defaultTaxId = null;
    }

    const taxSelect = document.getElementById('cart-tax');
    const pphSelect = document.getElementById('cart-pph');
    const pphField = document.getElementById('cart-pph-field');

    const regular = taxes.filter((tax) => !tax.is_pph);
    const withholding = taxes.filter((tax) => tax.is_pph);

    if (taxSelect) {
        taxSelect.innerHTML =
            `<option value="">None (0%)</option>` +
            regular
                .map(
                    (tax) =>
                        `<option value="${tax.id}" data-rate="${tax.rate}" data-type="${escapeHtml(
                            tax.type,
                        )}">${escapeHtml(`${tax.name} (${tax.rate}%)`)}</option>`,
                )
                .join('');
        if (defaultTaxId && regular.some((tax) => tax.id === defaultTaxId)) {
            taxSelect.value = String(defaultTaxId);
        }
    }

    if (pphSelect) {
        pphSelect.innerHTML =
            `<option value="">None</option>` +
            withholding
                .map(
                    (tax) =>
                        `<option value="${tax.id}" data-rate="${tax.rate}">${escapeHtml(
                            `${tax.name} (${tax.rate}%)`,
                        )}</option>`,
                )
                .join('');
    }
    if (pphField) {
        pphField.hidden = withholding.length === 0;
    }
}

// ------------------------------------------------------------------ payment

function updateChange() {
    const received = parseFloat(document.getElementById('pay-received')?.value || 0);
    const { grandTotal } = readTotals();
    const change = Math.max(0, (Number.isFinite(received) ? received : 0) - grandTotal);
    const el = document.getElementById('pay-change');
    if (el) {
        el.textContent = formatCurrency(change);
    }

    const submit = document.getElementById('pay-submit');
    if (submit && paymentMethod === 'Cash') {
        submit.disabled = (Number.isFinite(received) ? received : 0) < grandTotal;
    } else if (submit) {
        submit.disabled = false;
    }
}

function openPaymentSheet() {
    const { grandTotal } = readTotals();
    const totalEl = document.getElementById('pay-grand-total');
    if (totalEl) {
        totalEl.textContent = formatCurrency(grandTotal);
    }

    const received = document.getElementById('pay-received');
    if (received) {
        received.value = String(grandTotal);
    }
    const error = document.getElementById('pay-error');
    if (error) {
        error.hidden = true;
    }

    updateChange();
    openSheet('pay');
}

async function submitSale() {
    const { grandTotal, discountValue, discountType, tax, pph } = readTotals();
    const error = document.getElementById('pay-error');
    const submit = document.getElementById('pay-submit');

    const received = parseFloat(document.getElementById('pay-received')?.value || 0);
    const amountReceived = paymentMethod === 'Cash'
        ? (Number.isFinite(received) ? received : 0)
        : grandTotal;

    if (paymentMethod === 'Cash' && amountReceived < grandTotal) {
        if (error) {
            error.textContent = 'Amount received is less than the grand total.';
            error.hidden = false;
        }
        return;
    }

    submit.disabled = true;
    if (error) {
        error.hidden = true;
    }

    try {
        // Stock first: the API answers with the exact shortages instead of a
        // generic failure, so the cashier can fix quantities without guessing.
        const stock = await api('/api/v1/mobile/pos/validate-stock', {
            method: 'POST',
            body: {
                products: cart.map((line) => ({ id: line.id, quantity: line.quantity })),
            },
        });

        if (!stock.available) {
            const names = (stock.items || [])
                .map((item) => `${item.name} (${item.available} left)`)
                .join(', ');
            throw new Error(`Insufficient stock: ${names}`);
        }

        const result = await api('/api/v1/mobile/pos/sales', {
            method: 'POST',
            body: {
                transaction_date: new Date().toISOString().slice(0, 10),
                products: cart.map((line) => ({
                    id: line.id,
                    quantity: line.quantity,
                    price: line.price,
                })),
                discount_total: discountValue,
                discount_total_type: discountType,
                grand_total: grandTotal,
                payment_method: paymentMethod,
                amount_received: amountReceived,
                change_amount: Math.max(0, amountReceived - grandTotal),
                // Always send the key: PosService only falls back to the
                // tenant default tax when the field is absent entirely.
                tax_id: tax ? tax.id : null,
                pph_tax_id: pph ? pph.id : null,
            },
        });

        closeSheet('pay');
        closeSheet('cart');
        cart = [];
        saveCart();
        renderCart();

        await showReceipt(result.sale_id);
    } catch (err) {
        if (error) {
            error.textContent = err.message;
            error.hidden = false;
        } else {
            toast(err.message);
        }
    } finally {
        submit.disabled = false;
    }
}

async function showReceipt(saleId) {
    openSheet('receipt');
    const body = document.getElementById('receipt-body');
    if (body) {
        body.innerHTML = `<div class="spinner" role="status"></div>`;
    }

    try {
        const data = await api(`/api/v1/mobile/pos/receipt/${saleId}`);
        const sale = data.sale || {};
        const lines = (sale.sales_items || sale.salesItems || []).map((item) => {
            const product = item.product || {};
            const name = product.name || item.product_name || 'Item';
            return `
                <div class="receipt-item">
                    <span>${escapeHtml(name)} <span class="qty-label">&times; ${escapeHtml(
                        item.quantity,
                    )}</span></span>
                    <span>${escapeHtml(formatCurrency(item.total ?? item.subtotal ?? 0))}</span>
                </div>`;
        });

        if (body) {
            body.innerHTML = `
                <div class="receipt-head">
                    <div class="receipt-invoice">${escapeHtml(sale.invoice || '')}</div>
                    <div class="muted small">${escapeHtml(sale.order_date || '')} · ${escapeHtml(
                        sale.payment_type || paymentMethod,
                    )}</div>
                </div>
                ${lines.join('')}
                <div class="totals">
                    <div class="total-row"><span>Subtotal</span><span>${escapeHtml(
                        formatCurrency(data.sub_total),
                    )}</span></div>
                    ${
                        Number(data.order_discount_amount || 0) > 0
                            ? `<div class="total-row"><span>Discount</span><span>-${escapeHtml(
                                  formatCurrency(data.order_discount_amount),
                              )}</span></div>`
                            : ''
                    }
                    ${
                        Number(data.tax_amount || 0) > 0
                            ? `<div class="total-row"><span>Tax (${escapeHtml(
                                  data.tax_rate || 0,
                              )}%)</span><span>${escapeHtml(formatCurrency(data.tax_amount))}</span></div>`
                            : ''
                    }
                    ${
                        Number(data.pph_amount || 0) > 0
                            ? `<div class="total-row"><span>PPh</span><span>-${escapeHtml(
                                  formatCurrency(data.pph_amount),
                              )}</span></div>`
                            : ''
                    }
                    <div class="total-row grand"><span>Grand total</span><span>${escapeHtml(
                        formatCurrency(data.grand_total),
                    )}</span></div>
                    <div class="total-row"><span>Cash</span><span>${escapeHtml(
                        formatCurrency(data.amount_received),
                    )}</span></div>
                    <div class="total-row"><span>Change</span><span>${escapeHtml(
                        formatCurrency(data.change),
                    )}</span></div>
                </div>`;
        }
    } catch (err) {
        if (body) {
            body.innerHTML = `<p class="error-text">${escapeHtml(err.message)}</p>`;
        }
    }
}

// -------------------------------------------------------------------- wiring

function handleBarcode(raw) {
    const code = String(raw || '').trim();
    if (!code) {
        return false;
    }

    // Exact code match first — a hardware scanner types the whole code and
    // hits Enter, so that should add to the cart rather than filter the grid.
    const exact = catalog.find(
        (product) => product.code.toLowerCase() === code.toLowerCase(),
    );
    if (exact) {
        addToCart(exact);
        return true;
    }

    searchQuery = code;
    const input = document.getElementById('pos-search');
    if (input) {
        input.value = code;
    }
    loadCatalog();
    return false;
}

export function initPosPage() {
    if (!requireAuth()) {
        return;
    }

    loadCart();
    initSheets();
    renderCart();
    loadTaxes();
    loadCatalog();

    const search = document.getElementById('pos-search');
    if (search) {
        search.addEventListener(
            'input',
            debounce(() => {
                searchQuery = search.value.trim();
                loadCatalog();
            }, 300),
        );

        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleBarcode(search.value);
            }
        });
    }

    // The dedicated barcode field is where a HID scanner should be focused:
    // it swallows the scan then clears itself for the next one.
    const barcode = document.getElementById('pos-barcode-input');
    if (barcode) {
        barcode.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            handleBarcode(barcode.value);
            barcode.value = '';
        });
    }

    const scanButton = document.getElementById('pos-scan-button');
    if (scanButton) {
        scanButton.addEventListener('click', () => barcode && barcode.focus());
    }

    const grid = document.getElementById('pos-catalog');
    if (grid) {
        grid.addEventListener('click', (event) => {
            const card = event.target.closest('[data-product-id]');
            if (!card) {
                return;
            }
            const product = catalog.find((p) => p.id === Number(card.dataset.productId));
            if (product) {
                addToCart(product);
            }
        });
    }

    const loadMore = document.getElementById('pos-load-more');
    if (loadMore) {
        loadMore.addEventListener('click', () => loadCatalog({ append: true }));
    }

    const bar = document.getElementById('cart-bar');
    if (bar) {
        bar.addEventListener('click', () => openSheet('cart'));
    }

    const lines = document.getElementById('cart-lines');
    if (lines) {
        lines.addEventListener('click', (event) => {
            const button = event.target.closest('[data-qty]');
            if (!button) {
                return;
            }
            const row = button.closest('[data-line-id]');
            const line = cart.find((item) => item.id === Number(row.dataset.lineId));
            if (!line) {
                return;
            }
            line.quantity += Number(button.dataset.qty);
            if (line.quantity <= 0) {
                cart = cart.filter((item) => item.id !== line.id);
            }
            renderCart();
        });
    }

    const clear = document.getElementById('cart-clear');
    if (clear) {
        clear.addEventListener('click', () => {
            cart = [];
            renderCart();
        });
    }

    ['cart-discount', 'cart-discount-type', 'cart-tax', 'cart-pph'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', renderTotals);
            el.addEventListener('change', renderTotals);
        }
    });

    const checkout = document.getElementById('cart-checkout');
    if (checkout) {
        checkout.addEventListener('click', openPaymentSheet);
    }

    const methods = document.getElementById('pay-methods');
    if (methods) {
        methods.addEventListener('click', (event) => {
            const button = event.target.closest('[data-method]');
            if (!button) {
                return;
            }
            paymentMethod = button.dataset.method;
            methods
                .querySelectorAll('.method')
                .forEach((el) => el.classList.toggle('active', el === button));
            document.getElementById('pay-cash-block').hidden = paymentMethod !== 'Cash';
            updateChange();
        });
    }

    const received = document.getElementById('pay-received');
    if (received) {
        received.addEventListener('input', updateChange);
    }

    const exact = document.getElementById('pay-exact');
    if (exact) {
        exact.addEventListener('click', () => {
            const { grandTotal } = readTotals();
            if (received) {
                received.value = String(grandTotal);
            }
            updateChange();
        });
    }

    const submit = document.getElementById('pay-submit');
    if (submit) {
        submit.addEventListener('click', submitSale);
    }

    const newSale = document.getElementById('receipt-new-sale');
    if (newSale) {
        newSale.addEventListener('click', () => {
            closeSheet('receipt');
            window.location.assign('/m/pos');
        });
    }
}
