@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <script src="{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>

    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Dashboard
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body text-center py-4">
                            <i class="ti ti-circle-check icon text-success icon-lg mb-4"></i>
                            <h3 class="mb-3">Success!</h3>
                            <div class="text-secondary">
                                <div class="text-success text-start text-center">
                                    {{ session('success') }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var successModalElement = document.getElementById("successModal");
                    var successModal = new bootstrap.Modal(successModalElement);

                    setTimeout(() => {
                        successModal.show();
                        document.body.insertAdjacentHTML("beforeend",
                            '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
                    }, 5);

                    setTimeout(() => {
                        successModal.hide();
                        setTimeout(() => {
                            document.querySelector(".modal-backdrop-custom")?.remove();
                        }, 300);
                    }, 2000);
                });
            </script>
        @endif

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                        <i class="ti ti-building-warehouse fs-2"></i>
                                    </span>
                                    <div class="header">Remaining Liability</div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mt-2 ms-2">{{ \App\Helpers\CurrencyHelper::format($countliability) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                        <i class="ti ti-moneybag fs-2"></i>
                                    </span>
                                    <div class="header">Remaining Account Receivable</div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mt-2 ms-2">{{ \App\Helpers\CurrencyHelper::format($countRevenue) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                        <i class="ti ti-building-warehouse fs-2"></i>
                                    </span>
                                    <div class="header">Monthly Earning</div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mt-2 ms-2">{{ \App\Helpers\CurrencyHelper::format($totalDailySales) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-8">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="card-title">Financial Statement</div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                    <i class="ti ti-sum fs-2"></i>
                                                </span>
                                                Total Liabilities : <strong>
                                                    {{ \App\Helpers\CurrencyHelper::format($totalliability) }}</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                    <i class="ti ti-file-invoice fs-2"></i>
                                                </span>
                                                This Month Liability Paid : <strong>
                                                    {{ \App\Helpers\CurrencyHelper::format($liabilitypaymentMonthly) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                    <i class="ti ti-sum fs-2"></i>
                                                </span>
                                                Total Account Receivable : <strong>
                                                    {{ \App\Helpers\CurrencyHelper::format($totalRevenue) }}</strong>
                                            </div>
                                            <div class="mb-2">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                    <i class="ti ti-moneybag fs-2"></i>
                                                </span>
                                                This Month Receivable Paid :
                                                <strong>{{ \App\Helpers\CurrencyHelper::format($paidDebtMonthly) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-primary text-white avatar">
                                            <i class="ti ti-step-out fs-1"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            {{ $outCount }} Invoice OUT
                                        </div>
                                        <div class="text-secondary">
                                            {{ $outCountUnpaid }} waiting payments
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-green text-white avatar">
                                            <i class="ti ti-step-into fs-1"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            {{ $inCount }} Invoice IN
                                        </div>
                                        <div class="text-secondary">
                                            {{ $inCountUnpaid }} waiting payments
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">Overview</h3>

                                <!-- Tab Navigation -->
                                <ul class="nav nav-tabs" id="chartTabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="invoices-tab" data-bs-toggle="tab"
                                            href="#">Daily Invoices</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="earnings-tab" data-bs-toggle="tab" href="#">Daily
                                            Earnings</a>
                                    </li>
                                </ul>

                                <!-- Chart Container -->
                                <div id="chart-container" class="chart-lg mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            var chartElement = document.querySelector("#chart-container");

            if (!chartElement) {
                console.error("Chart container not found! Check if #chart-container exists in the DOM.");
                return;
            }

            var invoicesData = @json($chartData);
            var earningsData = @json($chartDataEarning);

            function formatDate(dateString) {
                let parts = dateString.split("-");
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
                return dateString;
            }

            function renderChart(type) {
                var options;
                if (type === "invoices") {
                    options = {
                        series: [{
                                name: "Invoices Count",
                                type: "bar",
                                data: invoicesData.map(item => item.invoice_count)
                            },
                            {
                                name: "Total Amount",
                                type: "line",
                                data: invoicesData.map(item => item.total_amount_raw)
                            }
                        ],
                        xaxis: {
                            categories: invoicesData.map(item => formatDate(item.date))
                        }
                    };
                } else {
                    options = {
                        series: [{
                            name: "Daily Earnings",
                            type: "line",
                            data: earningsData.map(item => item.total_earning)
                        }],
                        xaxis: {
                            categories: earningsData.map(item => formatDate(item.date))
                        }
                    };
                }

                options = {
                    ...options,
                    chart: {
                        type: "line",
                        height: 400
                    },
                    stroke: {
                        width: [0, 4]
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false
                        }
                    },
                    colors: ["#206bc4", "#f59f00"],
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return "{{ \App\Helpers\CurrencyHelper::format(0) }}".replace("0", val);
                            }
                        }
                    }
                };

                if (window.chartInstance) {
                    window.chartInstance.destroy();
                }

                window.chartInstance = new ApexCharts(chartElement, options);
                window.chartInstance.render();
            }

            // Initial Load
            renderChart("invoices");

            // Tab Click Events
            document.querySelector("#invoices-tab").addEventListener("click", function() {
                renderChart("invoices");
            });

            document.querySelector("#earnings-tab").addEventListener("click", function() {
                renderChart("earnings");
            });
        };
    </script>
@endsection
