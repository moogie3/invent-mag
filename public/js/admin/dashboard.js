/**
 * Financial Dashboard Charts
 *
 * This script handles the creation and management of all charts and interactive
 * elements on the financial dashboard.
 */
document.addEventListener("DOMContentLoaded", function () {
    // Check if dashboard data is available
    if (!window.dashboardData) {
        console.error("Dashboard data not available");
        return;
    }

    const { chartData, chartDataEarning, cashflowData } = window.dashboardData;
    const chartContainer = document.querySelector("#chart-container");

    if (!chartContainer) {
        console.error("Chart container not found");
        return;
    }

    // Format currency consistently across all charts
    const formatCurrency = (value) => {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
            minimumFractionDigits: 2,
        }).format(value);
    };

    // Initialize the main chart with default view (Revenue)
    let mainChart = createRevenueChart();

    // Tab switching functionality
    document.querySelectorAll("#chartTabs .nav-link").forEach((tab) => {
        tab.addEventListener("click", function (e) {
            e.preventDefault();

            // Update active tab styling
            document
                .querySelectorAll("#chartTabs .nav-link")
                .forEach((t) => t.classList.remove("active"));
            this.classList.add("active");

            // Destroy existing chart
            if (mainChart) {
                mainChart.destroy();
            }

            // Create appropriate chart based on selected tab
            if (this.id === "revenue-tab") {
                mainChart = createRevenueChart();
            } else if (this.id === "invoices-tab") {
                mainChart = createInvoicesChart();
            } else if (this.id === "cashflow-tab") {
                mainChart = createCashFlowChart();
            }
        });
    });

    // Time period selectors
    document
        .querySelectorAll("#chart-30days, #chart-90days, #chart-year")
        .forEach((btn) => {
            btn.addEventListener("click", function () {
                // Update active button styling
                document
                    .querySelectorAll(
                        "#chart-30days, #chart-90days, #chart-year"
                    )
                    .forEach((b) => b.classList.remove("btn-primary"));
                this.classList.add("btn-primary");

                // Get the time period from button ID
                const period = this.id.replace("chart-", "");
                updateChartPeriod(period);
            });
        });

    // Function to update chart period
    function updateChartPeriod(period) {
        // Determine which data source to use based on active tab
        const activeTab = document.querySelector("#chartTabs .nav-link.active");
        let sourceData;

        if (activeTab.id === "revenue-tab") {
            sourceData = chartDataEarning;
        } else if (activeTab.id === "invoices-tab") {
            sourceData = chartData;
        } else if (activeTab.id === "cashflow-tab") {
            sourceData = cashflowData;
        }

        if (!sourceData || !sourceData.length) {
            console.error("No data available for the selected chart type");
            return;
        }

        let sliceSize = sourceData.length; // Default to all data (annual)

        if (period === "30days") {
            sliceSize = Math.min(30, sourceData.length);
        } else if (period === "90days") {
            sliceSize = Math.min(90, sourceData.length);
        }

        // Get the data for the selected period
        const periodData = sourceData.slice(-sliceSize);

        // Update the active chart with new period data
        if (mainChart) {
            mainChart.updateOptions({
                xaxis: {
                    categories: periodData.map((item) => item.date),
                },
            });

            // Update series based on active tab
            if (activeTab.id === "revenue-tab") {
                mainChart.updateSeries([
                    {
                        name: "Revenue",
                        data: periodData.map((item) => item.total_amount_raw),
                    },
                ]);
            } else if (activeTab.id === "invoices-tab") {
                mainChart.updateSeries([
                    {
                        name: "Invoices Count",
                        type: "column",
                        data: periodData.map((item) => item.invoice_count || 0),
                    },
                    {
                        name: "Invoice Value",
                        type: "line",
                        data: periodData.map((item) => item.total_amount_raw),
                    },
                ]);
            } else if (activeTab.id === "cashflow-tab") {
                mainChart.updateSeries([
                    {
                        name: "Income",
                        data: periodData.map((item) => item.total_revenue || 0),
                    },
                    {
                        name: "Expenses",
                        data: periodData.map(
                            (item) => item.total_expenses || 0
                        ),
                    },
                    {
                        name: "Net Cash Flow",
                        data: periodData.map((item) => item.net_cashflow || 0),
                    },
                ]);
            }
        }
    }

    // Create Revenue Chart
    function createRevenueChart() {
        const options = {
            series: [
                {
                    name: "Revenue",
                    data: chartDataEarning.map((item) => item.total_amount_raw),
                },
            ],
            chart: {
                height: 350,
                type: "area",
                toolbar: {
                    show: false,
                },
                animations: {
                    enabled: true,
                    easing: "easeinout",
                    speed: 800,
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: "smooth",
                width: 2,
            },
            colors: ["#206bc4"],
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100],
                },
            },
            xaxis: {
                categories: chartDataEarning.map((item) => item.date),
                labels: {
                    style: {
                        colors: "#888",
                        fontSize: "12px",
                    },
                },
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    },
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    },
                },
            },
            grid: {
                borderColor: "#f1f1f1",
                strokeDashArray: 4,
            },
            markers: {
                size: 4,
                colors: ["#206bc4"],
                strokeColors: "#fff",
                strokeWidth: 2,
                hover: {
                    size: 6,
                },
            },
        };

        return new ApexCharts(chartContainer, options);
    }

    // Create Invoices Chart
    function createInvoicesChart() {
        const options = {
            series: [
                {
                    name: "Invoices Count",
                    type: "column",
                    data: chartData.map((item) => item.invoice_count || 0),
                },
                {
                    name: "Invoice Value",
                    type: "line",
                    data: chartData.map((item) => item.total_amount_raw),
                },
            ],
            chart: {
                height: 350,
                type: "line",
                toolbar: {
                    show: false,
                },
                stacked: false,
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                width: [0, 3],
                curve: "smooth",
            },
            colors: ["#82c91e", "#206bc4"],
            fill: {
                opacity: [0.85, 1],
                type: ["solid", "solid"],
            },
            xaxis: {
                categories: chartData.map((item) => item.date),
                labels: {
                    style: {
                        colors: "#888",
                        fontSize: "12px",
                    },
                },
            },
            yaxis: [
                {
                    title: {
                        text: "Invoices Count",
                    },
                    labels: {
                        formatter: function (val) {
                            return Math.round(val);
                        },
                    },
                },
                {
                    opposite: true,
                    title: {
                        text: "Invoice Value",
                    },
                    labels: {
                        formatter: function (val) {
                            return formatCurrency(val);
                        },
                    },
                },
            ],
            tooltip: {
                y: {
                    formatter: function (val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return Math.round(val) + " invoices";
                        }
                        return formatCurrency(val);
                    },
                },
            },
            legend: {
                position: "top",
            },
        };

        return new ApexCharts(chartContainer, options);
    }

    // Create Cash Flow Chart using the actual cashflowData
    function createCashFlowChart() {
        const options = {
            series: [
                {
                    name: "Income",
                    data: cashflowData.map((item) => item.total_revenue),
                },
                {
                    name: "Expenses",
                    data: cashflowData.map((item) => item.total_expenses),
                },
                {
                    name: "Net Cash Flow",
                    data: cashflowData.map((item) => item.net_cashflow),
                },
            ],
            chart: {
                height: 350,
                type: "line",
                toolbar: {
                    show: false,
                },
                dropShadow: {
                    enabled: true,
                    color: "#000",
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 0.2,
                },
            },
            colors: ["#82c91e", "#fa5252", "#206bc4"],
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: "smooth",
                width: [3, 3, 4],
            },
            xaxis: {
                categories: cashflowData.map((item) => item.date),
                labels: {
                    style: {
                        colors: "#888",
                        fontSize: "12px",
                    },
                },
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    },
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    },
                },
                shared: true,
                intersect: false,
            },
            grid: {
                borderColor: "#f1f1f1",
                strokeDashArray: 4,
            },
            markers: {
                size: 4,
                strokeWidth: 2,
                hover: {
                    size: 6,
                },
            },
            legend: {
                position: "top",
            },
        };

        return new ApexCharts(chartContainer, options);
    }

    // Initialize the chart with Revenue view by default
    if (mainChart) {
        mainChart.render();
    }

    // Add event listeners for export button if exists
    const exportButton = document.querySelector("a.btn.btn-white");
    if (exportButton) {
        exportButton.addEventListener("click", function (e) {
            e.preventDefault();

            // Create a CSV export of the current chart data
            const activeTab = document.querySelector(
                "#chartTabs .nav-link.active"
            );
            let fileName = "financial_report.csv";
            let data = chartDataEarning;

            if (activeTab.id === "revenue-tab") {
                fileName = "revenue_report.csv";
            } else if (activeTab.id === "invoices-tab") {
                fileName = "invoices_report.csv";
                data = chartData;
            } else if (activeTab.id === "cashflow-tab") {
                fileName = "cashflow_report.csv";
                data = cashflowData;
            }

            exportToCSV(data, fileName);
        });
    }

    // Export data to CSV
    function exportToCSV(data, filename) {
        const csvRows = [];

        // Get headers
        const headers = Object.keys(data[0] || {}).filter(
            (key) => !key.includes("_raw") && key !== "id"
        );
        csvRows.push(headers.join(","));

        // Add data rows
        for (const row of data) {
            const values = headers.map((header) => {
                const value = row[header];
                // Handle commas and quotes in values
                return `"${value}"`;
            });
            csvRows.push(values.join(","));
        }

        // Create and download CSV file
        const csvString = csvRows.join("\n");
        const blob = new Blob([csvString], { type: "text/csv;charset=utf-8;" });

        const link = document.createElement("a");
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // Add New Invoice button functionality
    const newInvoiceBtn = document.querySelector(
        "a.btn.btn-primary.d-none.d-sm-inline-block"
    );
    if (newInvoiceBtn) {
        newInvoiceBtn.addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = "/admin/sales/create";
        });
    }

    // Script to pass the dashboard data to the browser console (for development only)
    // Include this in window.dashboardData in your blade file
    /*

    */
});
