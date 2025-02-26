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
                                        <i class="ti ti-moneybag fs-2"></i>
                                    </span>
                                    <div class="header">Revenue from Customer</div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mt-2 ms-2">{{ \App\Helpers\CurrencyHelper::format($countcustrevenue) }}
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
                                    <div class="header">Liability</div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mt-2 ms-2">{{ \App\Helpers\CurrencyHelper::format($countliability) }}
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
                                <h3 class="card-title">Daily Invoices Overview</h3>
                                <div id="chart-daily-invoices" class="chart-lg"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            var chartElement = document.querySelector("#chart-daily-invoices");

            if (!chartElement) {
                console.error("Chart container not found! Check if #chart-daily-invoices exists in the DOM.");
                return;
            }

            var chartData = @json($chartData);

            // Convert date format from yy-mm-dd to dd-mm-yy
            function formatDate(dateString) {
                let parts = dateString.split("-");
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`; // Rearrange to dd-mm-yy
                }
                return dateString; // Return as is if format is incorrect
            }

            var options = {
                series: [{
                        name: "Invoices Count",
                        type: "bar",
                        data: chartData.map(item => item.invoice_count)
                    },
                    {
                        name: "Total Amount",
                        type: "line",
                        data: chartData.map(item => item.total_amount_raw)
                    }
                ],
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
                xaxis: {
                    categories: chartData.map(item => formatDate(item.date)) // Apply date formatting here
                },
                tooltip: {
                    y: {
                        formatter: function(val, {
                            seriesIndex
                        }) {
                            if (seriesIndex === 1) {
                                return "{{ \App\Helpers\CurrencyHelper::format(0) }}".replace("0", val);
                            }
                            return val;
                        }
                    }
                }
            };

            var chart = new ApexCharts(chartElement, options);
            chart.render();
        };
    </script>
@endsection
