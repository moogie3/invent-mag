<script>
(function() {
    // Prevent multiple executions from interfering with let/const
    // We use window properties for variables that MUST persist
    window.currencySettings = @json(\App\Helpers\CurrencyHelper::getSettings());
    
    // Use var for script-level variables to allow re-declaration without SyntaxError
    var currentChartType = window.currentChartType || 'sales';
    var currentPeriod = window.currentPeriod || '30days';

    function formatCurrency(value) {
        if (value === null || value === undefined) return '';
        var isNegative = value < 0;
        var absoluteValue = Math.abs(value);
        var formatted = new Intl.NumberFormat(window.currencySettings.locale, {
            minimumFractionDigits: window.currencySettings.decimal_places,
            maximumFractionDigits: window.currencySettings.decimal_places
        }).format(absoluteValue);
        var result = window.currencySettings.position === 'prefix'
            ? `${window.currencySettings.currency_symbol} ${formatted}`
            : `${formatted} ${window.currencySettings.currency_symbol}`;
        return isNegative ? `-${result}` : result;
    }

    var chartData = {
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

    @if(isset($salesForecast) && $salesForecast !== null)
    var salesForecastData = {
        labels: @json($salesForecast['labels'] ?? []),
        historical: @json($salesForecast['historical'] ?? []),
        forecast: @json($salesForecast['forecast'] ?? [])
    };
    @endif

    var chartConfigs = {
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
        if (typeof Chart === 'undefined') return null;
        
        var el = document.getElementById('performanceChart');
        if (!el) return null;
        
        // RELIABLE CLEANUP: Use Chart.js internal registry to find and kill existing chart on this canvas
        var existingChart = Chart.getChart(el);
        if (existingChart) {
            existingChart.destroy();
        }

        var ctx = el.getContext('2d');
        var config = chartConfigs[type];
        var dataset = chartData[type];

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
                    legend: { display: false },
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
                            callback: function(value) { return value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    }

    function createSalesForecastChart() {
        @if(isset($salesForecast) && $salesForecast !== null)
        if (typeof Chart === 'undefined') return null;

        var el = document.getElementById('sales-forecast-chart');
        if (!el || salesForecastData.labels.length === 0) return;
        
        var existingChart = Chart.getChart(el);
        if (existingChart) existingChart.destroy();

        var forecastCtx = el.getContext('2d');
        new Chart(forecastCtx, {
            type: 'line',
            data: {
                labels: salesForecastData.labels,
                datasets: [
                    {
                        label: '{{ __('messages.historical_sales') }}',
                        data: salesForecastData.historical,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: '{{ __('messages.forecasted_sales') }}',
                        data: salesForecastData.forecast,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) label += formatCurrency(context.parsed.y);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'month',
                            parser: 'yyyy-MM',
                            displayFormats: { month: 'MMM yyyy' }
                        },
                        title: { display: true, text: 'Month' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return formatCurrency(value); }
                        }
                    }
                }
            }
        });
        @endif
    }

    // Attach to window to make them accessible via onclick attributes in HTML
    window.switchChart = function(type) {
        window.currentChartType = type;
        createChart(type);
        document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
        var tab = document.getElementById(`${type}-tab`);
        if (tab) tab.classList.add('active');
    };

    window.updateChart = function(period) {
        if (period) window.currentPeriod = period;
        var chartContainer = document.querySelector('.chart-container');
        if (!chartContainer) return;

        chartContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border" role="status"></div></div>';

        fetch(`{{ route('admin.dashboard') }}?period=${period}&type=${window.currentChartType}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.labels && data.data) {
                chartData[window.currentChartType].labels = data.labels;
                chartData[window.currentChartType].data = data.data;
                chartData[window.currentChartType].formatted = data.formatted || data.data.map(val => val.toLocaleString());
            }
            chartContainer.innerHTML = '<canvas id="performanceChart"></canvas>';
            createChart(window.currentChartType);
        })
        .catch(err => console.error('Error:', err));
    };

    function handlePageLoad() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not yet loaded, retrying...');
            setTimeout(handlePageLoad, 100);
            return;
        }

        if (document.getElementById('performanceChart')) {
            createChart(window.currentChartType || 'sales');
        }
        if (document.getElementById('sales-forecast-chart')) {
            createSalesForecastChart();
        }

        var dateRangeSelect = document.querySelector('select[name="date_range"]');
        if (dateRangeSelect) {
            dateRangeSelect.onchange = function() {
                var customFields = document.querySelectorAll('#customDateStart, #customDateEnd');
                var display = this.value === 'custom' ? 'block' : 'none';
                customFields.forEach(f => {
                    f.style.display = display;
                    f.required = (display === 'block');
                });
            };
        }
    }

    // Immediate init
    handlePageLoad();

    // Turbo render listener (runs once per session)
    if (!window.dashboardEventsBound) {
        document.addEventListener('turbo:render', function() {
            if (document.getElementById('performanceChart')) {
                handlePageLoad();
            }
        });
        document.addEventListener('datarefresh', function() {
            if (document.getElementById('performanceChart')) {
                window.updateChart(window.currentPeriod);
            }
        });
        window.dashboardEventsBound = true;
    }
})();
</script>
