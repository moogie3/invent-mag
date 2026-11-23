// financial-health.js — the three 6-month trend charts on
// Accounting > Financial Health.
//
// The page passes its series through a JSON data island
// (#fh-trend-data, type="application/json" = never executed, so no CSP
// nonce needed — inline executable scripts break on Turbo visits because
// the document keeps the FIRST response's nonce).
//
// Idempotent per visit: canvases are marked via dataset.fhDrawn, and
// onPageLoad re-runs on every turbo:load with fresh DOM (fresh canvases).
import Chart from "chart.js/auto";
import "chartjs-adapter-date-fns";
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    const island = document.getElementById('fh-trend-data');
    if (!island || !document.getElementById('fh-chart-ledger')) return;
    if (typeof Chart === 'undefined') return; // CDN not ready — skip silently

    let data;
    try {
        data = JSON.parse(island.textContent);
    } catch (e) {
        return;
    }

    const money = (v) =>
        new Intl.NumberFormat('id', { notation: 'compact', maximumFractionDigits: 1 }).format(v);

    const base = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12 } } },
        scales: {
            y: { ticks: { callback: (v) => money(v) }, beginAtZero: true },
        },
    };

    const line = (label, values, color) => ({
        label,
        data: values,
        borderColor: color,
        backgroundColor: color + '22',
        fill: true,
        tension: 0.3,
        pointRadius: 2,
    });

    const mk = (id, datasets) => {
        const canvas = document.getElementById(id);
        if (!canvas || canvas.dataset.fhDrawn) return;
        canvas.dataset.fhDrawn = '1';
        new Chart(canvas, { type: 'line', data: { labels: data.labels, datasets }, options: base });
    };

    mk('fh-chart-ledger', [
        line(data.i18n.revenue, data.revenue_gl, '#206bc4'),
        line(data.i18n.expenses, data.expenses_gl, '#d9534f'),
    ]);
    mk('fh-chart-sub', [
        line(data.i18n.revenue, data.sales, '#2fb344'),
        line(data.i18n.purchases, data.purchases, '#f76707'),
    ]);
    mk('fh-chart-cash', [line(data.i18n.cash, data.cash, '#7048e8')]);
}, { id: 'financial-health' });

// ───────────────────────────────────────────────────────────────────
// Comparison modal — per-card drill-down + the "Compare all" table.
// Reads the #fh-compare-data island (same CSP-safe pattern as the charts)
// and exposes window.__fhOpenCompare(key) for the card buttons. `key` null
// opens the full table; a string opens that one metric.
// ───────────────────────────────────────────────────────────────────
onPageLoad(function () {
    const island = document.getElementById('fh-compare-data');
    const modalEl = document.getElementById('fhCompareModal');
    if (!island || !modalEl) return;

    let payload;
    try {
        payload = JSON.parse(island.textContent);
    } catch (e) {
        return;
    }

    const i18n = payload.i18n || {};
    const statusLabel = {
        good: i18n.healthy, warn: i18n.needs_attention,
        danger: i18n.critical, neutral: i18n.steady,
    };
    const toneClass = {
        good: 'text-success', warn: 'text-warning',
        danger: 'text-danger', neutral: 'text-secondary',
    };

    const fmtMoney = (v) =>
        new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v ?? 0);
    const fmtValue = (row, v) => {
        if (v === null || v === undefined) return '—';
        switch (row.display) {
            case 'percent': return (v * 100).toFixed(1) + '%';
            case 'ratio': return v.toFixed(1) + '×';
            case 'count': return String(Math.round(v));
            case 'days': return Math.round(v) + ' ' + (i18n.days || 'days');
            default: return fmtMoney(v);
        }
    };
    // Delta for a comparison is a raw difference in the metric's own units.
    const fmtDelta = (row, d) => {
        if (d === null || d === undefined) return '—';
        const sign = d > 0 ? '+' : (d < 0 ? '−' : '');
        const abs = Math.abs(d);
        switch (row.display) {
            case 'percent': return sign + (abs * 100).toFixed(1) + ' pp';
            case 'ratio': return sign + abs.toFixed(2) + '×';
            case 'count': return sign + Math.round(abs);
            case 'days': return sign + Math.round(abs) + ' ' + (i18n.days || 'days');
            default: return sign + fmtMoney(abs).replace('Rp', '').trim();
        }
    };
    const monthLabel = (ym) => {
        if (!ym) return '';
        const [y, m] = ym.split('-').map(Number);
        return new Date(y, (m || 1) - 1, 1)
            .toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
    };
    // A window is one month (default view) or a range (filtered period) —
    // render the range only when it actually spans more than one month.
    const windowLabel = (from, to) => {
        if (!to) return '';
        if (!from || from === to) return monthLabel(to);
        return `${monthLabel(from)} – ${monthLabel(to)}`;
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const sparkline = (canvasId, values, color, labels, fmt, opts) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined' || !values || !values.length) return;
        // Mini table sparklines: the row carries its own month axis so the
        // year span is readable at a glance. The cell is tall enough (40px)
        // for line + labels, and ticks are limited to the window's ends so
        // sixteen rows of month names never turn into noise.
        const showAxis = !!(opts && opts.axis);
        // Destroy any prior instance (modal reopens reuse the same canvas id).
        const prior = Chart.getChart(canvas);
        if (prior) prior.destroy();
        new Chart(canvas, {
            type: 'line',
            data: {
                // Real month names when the server sent them, so the shape
                // is readable without a second table underneath.
                labels: (labels && labels.length ? labels : values.map((_, i) => i + 1)),
                datasets: [{
                    data: values, borderColor: color,
                    backgroundColor: color + '22', fill: true,
                    tension: 0.3, pointRadius: values.length > 12 ? 0 : 2.5,
                    pointHoverRadius: 4,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 400 },
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        // Values only — the axis already names the month.
                        enabled: true, displayColors: false,
                        callbacks: {
                            label: (ctx) => (fmt ? fmt(ctx.parsed.y) : String(ctx.parsed.y)),
                        },
                    },
                },
                scales: {
                    x: {
                        display: showAxis,
                        ticks: {
                            maxTicksLimit: (opts && opts.maxTicks) || 6,
                            maxRotation: 0,
                            autoSkip: true,
                            color: '#868e96',
                            font: { size: 9 },
                        },
                        grid: { display: false },
                    },
                    y: { display: false },
                },
            },
        });
    };

    const statusBadge = (status) =>
        `<span class="badge bg-${status === 'good' ? 'success' : status === 'warn' ? 'warning' : status === 'danger' ? 'danger' : 'secondary'}-lt ${toneClass[status] || ''}">${esc(statusLabel[status] || status)}</span>`;

    const trendChip = (row) => {
        if (!row.trend) return '';
        const good = row.higher_is_better;
        const up = row.trend === 'up';
        let cls = 'secondary';
        if (row.trend === 'flat') cls = 'secondary';
        else if (good === null) cls = 'secondary';
        else if ((up && good) || (!up && !good)) cls = 'success';
        else cls = 'danger';
        const label = row.trend === 'up' ? i18n.trend_up : row.trend === 'down' ? i18n.trend_down : i18n.trend_flat;
        const arrow = row.trend === 'up' ? '↑' : row.trend === 'down' ? '↓' : '→';
        return `<span class="badge bg-${cls}-lt text-${cls}">${arrow} ${esc(label)}</span>`;
    };

    const renderSingle = (row) => {
        document.getElementById('fhCompareTitle').textContent = row.label;
        const body = document.getElementById('fhCompareBody');

        if (!row.comparable) {
            body.innerHTML = `
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="avatar avatar-sm bg-secondary-lt text-secondary"><i class="ti ti-lock"></i></span>
                    <div>
                        <div class="h3 mb-0">${esc(fmtValue(row, row.current))}</div>
                        <div class="text-muted small">${esc(row.reason || '')}</div>
                    </div>
                </div>
                <div class="alert alert-secondary d-flex align-items-start" role="alert">
                    <i class="ti ti-info-circle me-2 mt-1"></i>
                    <span>${esc(row.lock_reason || '')}</span>
                </div>`;
            return;
        }

        const color = row.trend === 'up'
            ? (row.higher_is_better === false ? '#d9534f' : '#2fb344')
            : row.trend === 'down'
                ? (row.higher_is_better === false ? '#2fb344' : '#d9534f')
                : '#868e96';

        body.innerHTML = `
            <div class="row text-center mb-3">
                <div class="col-6">
                    <div class="text-muted small mb-1">${esc(i18n.condition_now || '')} · ${esc(windowLabel(payload.compared.current_from, payload.compared.current))}</div>
                    <div class="h3 mb-0">${esc(fmtValue(row, row.current))}</div>
                    <div class="mt-1">${statusBadge(row.status)}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small mb-1">${esc(i18n.previous_month || '')} · ${esc(windowLabel(payload.compared.previous_from, payload.compared.previous))}</div>
                    <div class="h3 mb-0 text-muted">${esc(fmtValue(row, row.previous))}</div>
                    <div class="mt-1">${trendChip(row)}</div>
                </div>
            </div>
            <div class="d-flex justify-content-center mb-2">
                <span class="badge bg-secondary-lt text-secondary fs-6">
                    ${esc(i18n.compare_title || '')}: ${esc(fmtDelta(row, row.delta))}
                </span>
            </div>
            <div style="height: 110px"><canvas id="fh-spark-canvas"></canvas></div>
            <div class="text-muted small text-center mt-1">${esc(row.reason || '')}</div>`;

        sparkline('fh-spark-canvas', row.spark, color, row.spark_labels,
            (v) => fmtValue(row, v), { axis: true });
    };

    const renderAll = () => {
        document.getElementById('fhCompareTitle').textContent = i18n.compare_title || 'Comparison';
        const body = document.getElementById('fhCompareBody');
        const rows = payload.rows.map((row) => {
            const move = row.comparable
                ? `<div class="d-flex align-items-center gap-1 justify-content-end">${trendChip(row)}<span class="text-muted small">${esc(fmtDelta(row, row.delta))}</span></div>`
                : `<span class="text-muted small"><i class="ti ti-lock me-1"></i>—</span>`;
            // One tiny sparkline per comparable row: the shape of the move
            // beats any arrow, and locked rows keep the dash + lock. The cell
            // is 40px tall so the line and its month axis both fit without
            // bleeding into the row below.
            const spark = row.comparable && row.spark && row.spark.length > 1
                ? `<div style="height: 40px; width: 110px;"><canvas id="fh-spark-${esc(row.key)}"></canvas></div>`
                : '<span class="text-muted">—</span>';
            return `<tr>
                <td><div class="d-flex align-items-center gap-2"><i class="ti ti-arrows-left-right text-muted"></i><span>${esc(row.label)}</span></div></td>
                <td class="text-end">${esc(fmtValue(row, row.current))}</td>
                <td class="text-end text-muted">${row.comparable ? esc(fmtValue(row, row.previous)) : '—'}</td>
                <td>${spark}</td>
                <td class="text-end">${move}</td>
            </tr>`;
        }).join('');

        body.innerHTML = `
            <p class="text-muted small">${esc(i18n.compare_desc || '')}</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th>${esc(i18n.condition_now || '')}</th>
                            <th class="text-end">${esc(windowLabel(payload.compared.current_from, payload.compared.current))}</th>
                            <th class="text-end">${esc(i18n.previous_month || '')}</th>
                            <th class="text-center">${esc(i18n.trend_chart || '')}</th>
                            <th class="text-end">${esc(i18n.compare_title || '')}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;

        // Chart instances render after the DOM exists; colour follows the
        // same good/bad mapping the single view uses.
        payload.rows.forEach((row) => {
            if (!row.comparable || !row.spark || row.spark.length < 2) return;
            const color = row.trend === 'up'
                ? (row.higher_is_better === false ? '#d9534f' : '#2fb344')
                : row.trend === 'down'
                    ? (row.higher_is_better === false ? '#2fb344' : '#d9534f')
                    : '#868e96';
            sparkline('fh-spark-' + row.key, row.spark, color, row.spark_labels,
                (v) => fmtValue(row, v), { axis: true, maxTicks: 3 });
        });
    };

    const openModal = () => {
        // Own the open in JS — inline handlers are blocked by CSP and the
        // data-api path is unreliable under Turbo. Bootstrap is a global here.
        const instance = window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;
        if (instance) instance.show();
        else modalEl.classList.add('show');
    };

    window.__fhOpenCompare = function (key) {
        const row = key ? payload.rows.find((r) => r.key === key) : null;
        if (key && row) renderSingle(row);
        else renderAll();
        openModal();
    };

    // Wire the per-card buttons and the "Compare all" opener. Re-run per turbo
    // visit — fresh DOM each time, and onPageLoad's done-flag guards re-binding.
    document.querySelectorAll('.fh-compare-btn').forEach((btn) => {
        btn.addEventListener('click', () => window.__fhOpenCompare(btn.dataset.fhKey));
    });
    const allBtn = document.getElementById('fhCompareAllBtn');
    if (allBtn) allBtn.addEventListener('click', () => window.__fhOpenCompare(null));

    document.getElementById('fhCompareMonths').textContent = (i18n.compared_months || '')
        .replace('{current}', windowLabel(payload.compared.current_from, payload.compared.current))
        .replace('{previous}', windowLabel(payload.compared.previous_from, payload.compared.previous));
}, { id: 'financial-health-compare' });

// ───────────────────────────────────────────────────────────────────
// Period filter — the form itself is plain GET (works with JS off);
// this only keeps the preset select and the custom month inputs in
// sync: picking a preset hides + disables the inputs so a preset URL
// never carries stale from/to, and "Custom Range" brings them back.
// ───────────────────────────────────────────────────────────────────
onPageLoad(function () {
    const form = document.getElementById('fhPeriodForm');
    if (!form) return;
    const preset = document.getElementById('fhPresetSelect');
    // The inputs carry .fh-custom-range themselves (no wrapper divs), so
    // the same NodeList toggles visibility and submission together.
    const rangeInputs = form.querySelectorAll('.fh-custom-range');

    const sync = () => {
        const custom = preset.value === 'custom';
        // Disabled inputs are not submitted — a preset URL stays clean
        // (?preset=last_3) and never overrides the preset server-side.
        rangeInputs.forEach((el) => {
            el.classList.toggle('d-none', !custom);
            el.disabled = !custom;
        });
    };

    preset.addEventListener('change', sync);
    sync();
}, { id: 'financial-health-period' });

// ───────────────────────────────────────────────────────────────────
// "Understanding These Metrics" — per-entry accordions. Every metric
// title is a button that reveals only its own paragraph; the section
// intro and color legend stay visible. Bootstrap Collapse via JS: CSP
// blocks inline handlers and the data-api path is unreliable under
// Turbo visits. One delegated listener covers all current and future
// entries (the explainer is array-driven in the blade).
// ───────────────────────────────────────────────────────────────────
onPageLoad(function () {
    const items = document.querySelectorAll('.fh-explainer-item');
    if (!items.length) return;

    items.forEach((btn) => {
        btn.addEventListener('click', () => {
            const panel = document.getElementById(btn.getAttribute('aria-controls'));
            const chevron = btn.querySelector('.fh-explainer-chevron');
            if (!panel) return;
            const open = panel.classList.contains('show');
            if (window.bootstrap && window.bootstrap.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false })[open ? 'hide' : 'show']();
            } else {
                panel.classList.toggle('show', !open);
            }
            btn.setAttribute('aria-expanded', String(!open));
            // Chevron points right when closed, down when open.
            if (chevron) chevron.className = `ti ${open ? 'ti-chevron-right' : 'ti-chevron-down'} text-muted me-2 fh-explainer-chevron`;
        });
    });
}, { id: 'financial-health-explainer' });

// ───────────────────────────────────────────────────────────────────
// Dashboard strip — the same animated cash/revenue chart the full page
// draws, at strip scale. Reads the #fh-strip-data island (CSP-safe).
// ───────────────────────────────────────────────────────────────────
onPageLoad(function () {
    const island = document.getElementById('fh-strip-data');
    const canvas = document.getElementById('fh-strip-chart');
    if (!island || !canvas || canvas.dataset.fhDrawn) return;
    if (typeof Chart === 'undefined') return;

    let data;
    try {
        data = JSON.parse(island.textContent);
    } catch (e) {
        return;
    }
    canvas.dataset.fhDrawn = '1';

    const money = (v) =>
        new Intl.NumberFormat('id', { notation: 'compact', maximumFractionDigits: 1 }).format(v);

    const line = (label, values, color) => ({
        label,
        data: values,
        borderColor: color,
        backgroundColor: color + '22',
        fill: true,
        tension: 0.3,
        pointRadius: 2,
    });

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                line(data.i18n.cash, data.cash, '#7048e8'),
                line(data.i18n.revenue, data.revenue, '#206bc4'),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12 } } },
            scales: { y: { ticks: { callback: (v) => money(v) }, beginAtZero: true } },
        },
    });
}, { id: 'financial-health-strip' });
