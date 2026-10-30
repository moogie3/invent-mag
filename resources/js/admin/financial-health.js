// financial-health.js — the three 6-month trend charts on
// Accounting > Financial Health.
//
// The page passes its series through a JSON data island
// (#fh-trend-data, type="application/json" = never executed, so no CSP
// nonce needed — inline executable scripts break on Turbo visits because
// the document keeps the FIRST response's nonce). Chart.js itself is the
// global head CDN build, same as every other chart in the app.
//
// Idempotent per visit: canvases are marked via dataset.fhDrawn, and
// onPageLoad re-runs on every turbo:load with fresh DOM (fresh canvases).
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
