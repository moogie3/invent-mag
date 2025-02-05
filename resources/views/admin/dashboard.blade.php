@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <div class="page-wrapper">
        <!-- Page header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <!-- Page pre-title -->
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
        <!-- Page body -->
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
                                            12 waiting payments today
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

