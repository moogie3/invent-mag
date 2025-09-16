<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let currencySettings = @json(\App\Helpers\CurrencyHelper::getSettings());
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
        if (performanceChart) {
            performanceChart.destroy();
        }
        performanceChart = createChart(type);

        // Update active tab
        document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
        document.getElementById(`${type}-tab`).classList.add('active');
    }

    function updateChart(period) {
        currentPeriod = period;

        // Show loading state
        const chartContainer = document.querySelector('.chart-container');
        const originalContent = chartContainer.innerHTML;
        chartContainer.innerHTML =
            '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(`{{ route('admin.dashboard') }}?period=${period}&type=${currentChartType}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Update chart data
                if (data.labels && data.data) {
                    chartData[currentChartType].labels = data.labels;
                    chartData[currentChartType].data = data.data;
                    chartData[currentChartType].formatted = data.formatted || data.data.map(val => val
                        .toLocaleString());
                }

                // Restore chart container and recreate chart
                chartContainer.innerHTML = '<canvas id="performanceChart"></canvas>';
                switchChart(currentChartType);

                // Close dropdown after selection
                const dropdown = document.querySelector('.dropdown-toggle');
                if (dropdown && bootstrap?.Dropdown) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown);
                    if (dropdownInstance) {
                        dropdownInstance.hide();
                    }
                }
            })
            .catch(error => {
                console.error('Error updating chart:', error);

                // Restore original content on error
                chartContainer.innerHTML = originalContent;

                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger alert-dismissible fade show';
                errorDiv.innerHTML = `
                    <strong>Error!</strong> Failed to update chart data. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                chartContainer.parentNode.insertBefore(errorDiv, chartContainer);

                // Reinitialize chart
                setTimeout(() => {
                    if (!performanceChart) {
                        initChart();
                    }
                }, 100);
            });
    }

    function applyFilter() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);
        window.location.href = `{{ route('admin.dashboard') }}?${params.toString()}`;
    }

    function resetFilter() {
        document.getElementById('filterForm').reset();
        window.location.href = `{{ route('admin.dashboard') }}`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        initChart();

        // Handle custom date range toggle
        const dateRangeSelect = document.querySelector('select[name="date_range"]');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', function() {
                const customFields = document.querySelectorAll('#customDateStart, #customDateEnd');
                const display = this.value === 'custom' ? 'block' : 'none';
                customFields.forEach(field => {
                    field.style.display = display;
                    if (display === 'block') {
                        field.required = true;
                    } else {
                        field.required = false;
                    }
                });
            });
        }

        // Listen for the global datarefresh event
        document.addEventListener('datarefresh', function() {
            console.log('datarefresh event received, updating chart.');
            updateChart(currentPeriod);
        });
    });
</script>
