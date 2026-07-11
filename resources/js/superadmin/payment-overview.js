import Chart from 'chart.js/auto';
import { onPageLoad } from '../admin/utils/onPageLoad.js';

onPageLoad(function () {
    var chartDataEl = document.getElementById('paymentOverviewChartData');
    if (!chartDataEl) return;

    var chartData = JSON.parse(chartDataEl.getAttribute('data-chart') || 'null');
    if (!chartData) return;

    if (chartData.monthlyRevenue && chartData.monthlyRevenue.labels && chartData.monthlyRevenue.labels.length > 0) {
        var ctx = document.getElementById('monthlyRevenueChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.monthlyRevenue.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: chartData.monthlyRevenue.data,
                        backgroundColor: '#2563eb',
                        borderRadius: 6,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: function (v) { return '$' + v.toLocaleString(); } }
                        }
                    }
                }
            });
        }
    }

    if (chartData.revenueByGateway && chartData.revenueByGateway.labels && chartData.revenueByGateway.labels.length > 0) {
        var ctx2 = document.getElementById('gatewayChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: chartData.revenueByGateway.labels,
                    datasets: [{
                        data: chartData.revenueByGateway.data,
                        backgroundColor: ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#8b5cf6'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
                    }
                }
            });
        }
    }
});
