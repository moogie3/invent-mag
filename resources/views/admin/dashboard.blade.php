@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
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
                    {{-- CHART --}}
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">Overview</h3>
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
                                <div id="chart-container" class="chart-lg mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
