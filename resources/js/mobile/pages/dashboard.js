import { api, msg, requireAuth } from '../api.js';
import { formatCurrency, formatNumber } from '../currency.js';
import { escapeHtml, initSheets, toast } from '../ui.js';

/** Status badge class shared with the web dashboard's recent-transaction table. */
function statusBadge(status) {
    if (status === 'Paid') {
        return 'badge-success';
    }
    if (status === 'Partial') {
        return 'badge-warning';
    }
    return 'badge-danger';
}

function renderKeyMetrics(metrics) {
    const grid = document.getElementById('kpi-grid');
    if (!grid) {
        return;
    }

    // The accent cycles from CSS (nth-child) so no metric needs a bespoke
    // icon — the titles come from DashboardService and are not stable enough
    // to match on.
    grid.innerHTML = (metrics || [])
        .map((metric, index) => {
            const value =
                metric.format === 'currency'
                    ? formatCurrency(metric.value)
                    : formatNumber(metric.value);
            const trendClass = metric.trend === 'positive' ? 'badge-success' : 'badge-danger';
            const label = metric.trend_label
                ? `<span class="badge ${trendClass}">${escapeHtml(metric.trend_label)}</span>`
                : '';

            return `
                <div class="kpi" style="--i: ${index}">
                    <span class="kpi-title">${escapeHtml(metric.title)}</span>
                    <span class="kpi-value">${escapeHtml(value)}</span>
                    <span class="kpi-meta">${label}</span>
                </div>`;
        })
        .join('');
}

function renderStats(data) {
    const list = document.getElementById('stat-list');
    if (!list) {
        return;
    }

    const rows = [
        ['Total revenue', formatCurrency(data.total_revenue)],
        ['Monthly sales', formatCurrency(data.monthly_sales)],
        ['Unpaid revenue', formatCurrency(data.unpaid_revenue)],
        ['Unpaid liability', formatCurrency(data.unpaid_liability)],
        ['Low stock items', formatNumber(data.low_stock_count)],
        ['Collection rate', `${Number(data.collection_rate || 0).toFixed(1)}%`],
        ['Avg. due days', `${formatNumber(data.avg_due_days)} d`],
    ];

    list.innerHTML = rows
        .map(
            ([label, value]) => `
                <div class="stat">
                    <div class="stat-label">${escapeHtml(label)}</div>
                    <div class="stat-value">${escapeHtml(value)}</div>
                </div>`,
        )
        .join('');
}

function renderRecentSales(sales) {
    const host = document.getElementById('recent-sales');
    if (!host) {
        return;
    }

    if (!sales || sales.length === 0) {
        host.innerHTML = `<p class="empty">${escapeHtml(
            msg('msgNoTransactions', 'No transactions yet.'),
        )}</p>`;
        return;
    }

    host.innerHTML = sales
        .map(
            (sale) => `
                <div class="list-row">
                    <div class="list-main">
                        <div class="list-title">${escapeHtml(sale.invoice)}</div>
                        <div class="list-sub">${escapeHtml(sale.customer?.name || 'Walk-in')}</div>
                    </div>
                    <div class="list-end">
                        <div class="list-amount">${escapeHtml(formatCurrency(sale.total))}</div>
                        <span class="badge ${statusBadge(sale.status)}">${escapeHtml(sale.status)}</span>
                    </div>
                </div>`,
        )
        .join('');
}

async function loadDashboard(range) {
    const loading = document.getElementById('dashboard-loading');
    const errorBox = document.getElementById('dashboard-error');
    const errorText = document.getElementById('dashboard-error-text');
    const body = document.getElementById('dashboard-body');

    if (loading) {
        loading.hidden = false;
    }
    if (body) {
        body.hidden = true;
    }
    if (errorBox) {
        errorBox.hidden = true;
    }

    try {
        const data = await api(`/api/v1/mobile/dashboard?date_range=${encodeURIComponent(range)}`);

        renderKeyMetrics(data.key_metrics);
        renderStats(data);
        renderRecentSales(data.recent_sales);

        if (body) {
            body.hidden = false;
            // Reveal once, on first paint: replaying the entrance on every
            // range change would make the page feel like it is restarting.
            if (!body.dataset.revealed) {
                body.dataset.revealed = '1';
                body.classList.add('is-in');
            }
        }
    } catch (err) {
        if (err.status === 403) {
            errorText.textContent = 'Your account does not have access to the dashboard.';
        } else {
            errorText.textContent = err.message;
        }
        if (errorBox) {
            errorBox.hidden = false;
        }
    } finally {
        if (loading) {
            loading.hidden = true;
        }
    }
}

function initRangeControl(onChange) {
    const buttons = Array.from(document.querySelectorAll('.seg-btn'));
    if (buttons.length === 0) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.classList.contains('is-active')) {
                return;
            }

            buttons.forEach((other) => {
                other.classList.toggle('is-active', other === button);
                other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
            });

            onChange(button.dataset.range);
        });
    });
}

export function initDashboardPage() {
    if (!requireAuth()) {
        return;
    }

    initSheets();

    initRangeControl((range) => loadDashboard(range));
    loadDashboard('this_month');
    initInstallBanner();
}

/**
 * beforeinstallprompt is only fired once per session and only when the
 * browser thinks the app is installable, so the deferred event is held here
 * until the user actually taps Install.
 */
function initInstallBanner() {
    const banner = document.getElementById('install-banner');
    const button = document.getElementById('install-button');
    if (!banner || !button) {
        return;
    }

    // Installed (or iOS Safari): the banner would be a dead button.
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }

    let deferred = null;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferred = event;
        banner.hidden = false;
    });

    button.addEventListener('click', async () => {
        if (!deferred) {
            toast('Use your browser menu to add this app to the home screen.');
            return;
        }
        deferred.prompt();
        await deferred.userChoice;
        deferred = null;
        banner.hidden = true;
    });

    window.addEventListener('appinstalled', () => {
        banner.hidden = true;
    });
}
