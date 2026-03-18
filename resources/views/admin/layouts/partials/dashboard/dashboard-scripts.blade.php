<script>
(function() {
    window.currencySettings = @json(\App\Helpers\CurrencyHelper::getSettings());

    // Always reset currentChartType on fresh page load.
    // currentDateRange tracks the active date_range filter (default: this_year, matching the page load query).
    // currentPeriod is only used by the legacy period-based path (not used by datarefresh).
    window.currentChartType = 'sales';
    window.currentDateRange = window.currentDateRange || 'this_year';
    window.currentPeriod = window.currentPeriod || '30days';

    // Store chart data on window so it survives across IIFE re-executions and
    // remains accessible from event listeners bound in previous page loads.
    window.chartData = {
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
    window.salesForecastData = {
        labels: @json($salesForecast['labels'] ?? []),
        historical: @json($salesForecast['historical'] ?? []),
        forecast: @json($salesForecast['forecast'] ?? [])
    };
    @else
    window.salesForecastData = null;
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

    window.createChart = function(type) {
        if (typeof Chart === 'undefined') return null;

        // Normalise type — fall back to 'sales' if invalid
        var safeType = (type === 'purchases') ? 'purchases' : 'sales';

        var el = document.getElementById('performanceChart');
        if (!el) return null;

        var existingChart = Chart.getChart(el);
        if (existingChart) existingChart.destroy();

        var ctx = el.getContext('2d');
        var config = chartConfigs[safeType];
        var dataset = window.chartData[safeType];

        if (!config || !dataset) return null;

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
    };

    function createSalesForecastChart() {
        if (typeof Chart === 'undefined') return null;
        if (!window.salesForecastData || window.salesForecastData.labels.length === 0) return;

        var el = document.getElementById('sales-forecast-chart');
        if (!el) return;

        var existingChart = Chart.getChart(el);
        if (existingChart) existingChart.destroy();

        var forecastCtx = el.getContext('2d');
        new Chart(forecastCtx, {
            type: 'line',
            data: {
                labels: window.salesForecastData.labels,
                datasets: [
                    {
                        label: '{{ __('messages.historical_sales') }}',
                        data: window.salesForecastData.historical,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: '{{ __('messages.forecasted_sales') }}',
                        data: window.salesForecastData.forecast,
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
    }

    window.switchChart = function(type) {
        window.currentChartType = (type === 'purchases') ? 'purchases' : 'sales';
        window.createChart(window.currentChartType);
        document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
        var tab = document.getElementById(`${window.currentChartType}-tab`);
        if (tab) tab.classList.add('active');
    };

    window.updateChart = function(period) {
        // period kept for backward compat with any external callers; we now prefer date_range.
        if (period) window.currentPeriod = period;
        var chartContainer = document.querySelector('.chart-container');
        if (!chartContainer) return;

        var type = (window.currentChartType === 'purchases') ? 'purchases' : 'sales';

        chartContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border" role="status"></div></div>';

        // Use date_range so the AJAX refresh returns the same monthly data as the initial page load.
        var url = `{{ route('admin.dashboard') }}?date_range=${encodeURIComponent(window.currentDateRange)}&type=${type}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Server returned ' + res.status);
            return res.json();
        })
        .then(function(data) {
            if (data.labels && data.data && window.chartData[type]) {
                window.chartData[type].labels = data.labels;
                window.chartData[type].data = data.data;
                window.chartData[type].formatted = data.formatted || data.data.map(function(val) { return val.toLocaleString(); });
            }
            chartContainer.innerHTML = '<canvas id="performanceChart"></canvas>';
            window.createChart(type);
        })
        .catch(function(err) {
            console.error('Chart update error:', err);
            chartContainer.innerHTML = '<canvas id="performanceChart"></canvas>';
            window.createChart(type);
        });
    };

    function handlePageLoad() {
        if (typeof Chart === 'undefined') {
            setTimeout(handlePageLoad, 100);
            return;
        }

        if (document.getElementById('performanceChart')) {
            window.createChart(window.currentChartType);
        }
        if (document.getElementById('sales-forecast-chart')) {
            createSalesForecastChart();
        }

        var dateRangeSelect = document.querySelector('select[name="date_range"]');
        if (dateRangeSelect) {
            // Sync currentDateRange so auto-refresh uses the same filter the user selected
            window.currentDateRange = dateRangeSelect.value || 'this_year';
            dateRangeSelect.onchange = function() {
                window.currentDateRange = this.value || 'this_year';
                var customFields = document.querySelectorAll('#customDateStart, #customDateEnd');
                var display = this.value === 'custom' ? 'block' : 'none';
                customFields.forEach(function(f) {
                    f.style.display = display;
                    f.required = (display === 'block');
                });
            };
        }
    }

    handlePageLoad();

    // Remove old listeners before re-binding to avoid duplicate handlers on Turbo re-renders
    if (window._dashboardTurboHandler) {
        document.removeEventListener('turbo:render', window._dashboardTurboHandler);
    }
    if (window._dashboardRefreshHandler) {
        document.removeEventListener('datarefresh', window._dashboardRefreshHandler);
    }

    window._dashboardTurboHandler = function() {
        if (document.getElementById('performanceChart')) {
            handlePageLoad();
        }
    };
    window._dashboardRefreshHandler = function() {
        if (document.getElementById('performanceChart')) {
            window.updateChart(window.currentPeriod);
        }
    };

    document.addEventListener('turbo:render', window._dashboardTurboHandler);
    document.addEventListener('datarefresh', window._dashboardRefreshHandler);
})();
</script>
