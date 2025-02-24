@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <script src="{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1692870487') }}" defer></script>
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
            <div class="modal modal-blur fade show" id="modal-success" tabindex="-1" role="dialog" aria-hidden="true"
                style="display: block; background: rgba(0, 0, 0, 0.6);">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-status bg-success"></div>
                        <div class="modal-body text-center py-4">
                            <!-- Success Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-success icon-lg" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                            <h3>Success!</h3>
                            <div class="text-secondary">{{ session('success') }}</div>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <a href="#" class="btn btn-success w-100" data-bs-dismiss="modal">OK</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        let successModal = document.getElementById("modal-success");
                        successModal.style.display = "none";
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
                                    <div class="header">Revenue</div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mt-2 ms-2">Rp.- 1.000.000.000</div>
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
                                    <div class="h1 mt-2 ms-2">2,986</div>
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
                                            35 Invoice OUT
                                        </div>
                                        <div class="text-secondary">
                                            12 waiting payments
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
                                            78 Invoice IN
                                        </div>
                                        <div class="text-secondary">
                                            10 waiting payments
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">Monthly Revenue</h3>
                                <div id="chart-mentions" class="chart-lg"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
