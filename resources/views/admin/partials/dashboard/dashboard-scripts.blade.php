<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let performanceChart;
    let currentChartType = 'sales';
    let currentPeriod = '30days';

    const chartData = {
        sales: {
            labels: @json($chartLabels ?? []),
            data: @json($chartData ?? []),
            formatted: @json(collect($chartData ?? [])->map(fn($val) => \App\Helpers\CurrencyHelper::format($val)))
        },
        purchases: {
            labels: @json($purchaseChartLabels ?? []),
            data: @json($purchaseChartData ?? []),
            formatted: @json(collect($purchaseChartData ?? [])->map(fn($val) => \App\Helpers\CurrencyHelper::format($val)))
        }
    };

    const chartConfigs = {
        sales: {
            type: 'line',
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            label: 'Sales'
        },
        purchases: {
            type: 'line',
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            label: 'Purchases'
        }
    };

    function createChart(type) {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const config = chartConfigs[type];
        const dataset = chartData[type];

        return new Chart(ctx, {
            type: config.type,
            data: {
                labels: dataset.labels,
                datasets: [{
                    label: config.label,
                    data: dataset.data,
                    borderColor: config.borderColor,
                    backgroundColor: config.backgroundColor,
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return dataset.formatted[context.dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    function initChart() {
        performanceChart = createChart('sales');
    }

    function switchChart(type) {
        currentChartType = type;
        performanceChart.destroy();
        performanceChart = createChart(type);
    }

    function updateChart(period) {
        currentPeriod = period;
        fetch(`{{ route('admin.reports') }}?period=${period}&type=${currentChartType}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                Object.assign(chartData[currentChartType], data);
                switchChart(currentChartType);
            })
            .catch(error => console.error('Error updating chart:', error));
    }

    function applyFilter() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);
        window.location.href = `{{ route('admin.reports') }}?${params.toString()}`;
    }

    function resetFilter() {
        document.getElementById('filterForm').reset();
        window.location.href = `{{ route('admin.reports') }}`;
    }

    document.addEventListener('DOMContentLoaded', initChart);

    // Handle custom date range toggle
    document.querySelector('select[name="date_range"]')?.addEventListener('change', function() {
        const customFields = document.querySelectorAll('#customDateStart, #customDateEnd');
        const display = this.value === 'custom' ? 'block' : 'none';
        customFields.forEach(field => field.style.display = display);
    });
</script>

<style>
    @media print {

        .btn,
        .dropdown,
        .modal,
        .page-header .col-auto {
            display: none !important;
        }

        .card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }

        .page-title {
            font-size: 24px !important;
            margin-bottom: 20px !important;
        }
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    .card {
        transition: transform 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .progress-sm {
        height: 0.5rem;
    }

    .avatar {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .badge {
        font-size: 0.75rem;
    }

    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
