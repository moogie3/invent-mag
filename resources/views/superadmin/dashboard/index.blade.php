@extends('superadmin.layouts.base')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Good morning, Super Admin</h2>
                    <div class="text-muted mt-1">Welcome back! Here's an overview of your SaaS platform.</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <span class="d-none d-sm-inline">
                            <span class="text-muted"><i class="ti ti-calendar me-1"></i> {{ \Carbon\Carbon::now()->format('l, M d, Y') }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <!-- Metric Cards -->
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="subheader">TOTAL TENANTS</div>
                                <div class="ms-auto lh-1 text-primary">
                                    <div class="avatar bg-primary-lt avatar-sm rounded"><i class="ti ti-building"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-3">{{ $totalTenants }}</div>
                            <div class="d-flex mb-2">
                                <a href="#" class="text-primary text-decoration-none fw-medium fs-5">View all tenants <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="subheader">ACTIVE / TRIAL</div>
                                <div class="ms-auto lh-1 text-success">
                                    <div class="avatar bg-success-lt avatar-sm rounded"><i class="ti ti-check"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-3">{{ $activeTenants }}</div>
                            <div class="d-flex mb-2">
                                <a href="#" class="text-success text-decoration-none fw-medium fs-5">View subscriptions <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="subheader">ACTIVE PLANS</div>
                                <div class="ms-auto lh-1 text-info">
                                    <div class="avatar bg-info-lt avatar-sm rounded"><i class="ti ti-tag"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-3">{{ $activePlans }}</div>
                            <div class="d-flex mb-2">
                                <a href="#" class="text-info text-decoration-none fw-medium fs-5">Manage plans <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="subheader">TOTAL SUBSCRIPTIONS</div>
                                <div class="ms-auto lh-1 text-warning">
                                    <div class="avatar bg-warning-lt avatar-sm rounded"><i class="ti ti-chart-pie"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-3">{{ $totalSubscriptions }}</div>
                            <div class="d-flex mb-2">
                                <span class="text-success fs-5">{{ $activeSubscriptions }} active</span>
                                <span class="text-muted ms-2 fs-5">, {{ $trialSubscriptions }} trial</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row row-deck row-cards mb-4">
                <!-- Tenants Chart -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                            <h3 class="card-title"><i class="ti ti-trending-up me-2 text-muted"></i>Tenants created (last 6 months)</h3>
                            <div class="card-actions">
                                <span class="badge bg-secondary-lt">Last 6 months</span>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-0">
                            <div id="tenants-chart" style="min-height: 250px;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Subscription Status -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                            <h3 class="card-title"><i class="ti ti-chart-donut me-2 text-muted"></i>Subscription status</h3>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center justify-content-center pt-0">
                            <div id="subscription-status-chart" style="min-height: 200px;"></div>
                            <div class="d-flex justify-content-center gap-3 mt-3">
                                <div class="d-flex align-items-center"><span class="badge bg-success badge-blink me-2"></span> Active</div>
                                <div class="d-flex align-items-center"><span class="badge bg-primary badge-blink me-2"></span> Trial</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row row-deck row-cards">
                <!-- Subscriptions by Plan -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                            <h3 class="card-title"><i class="ti ti-chart-bar me-2 text-muted"></i>Subscriptions by plan (top 5)</h3>
                            <div class="card-actions">
                                <span class="badge bg-secondary-lt">Top 5</span>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-0">
                            <div id="plans-chart" style="min-height: 250px;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3 bg-transparent">
                        <div class="card-header border-0 pb-2 pt-0 px-0">
                            <h3 class="card-title"><i class="ti ti-bolt me-2 text-muted"></i>Quick actions</h3>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="#" class="card card-link card-link-pop shadow-sm border-0 rounded-3 text-center py-4 text-decoration-none">
                                    <div class="avatar bg-primary-lt mx-auto mb-3"><i class="ti ti-building-community"></i></div>
                                    <div class="fw-bold text-body">Manage tenants</div>
                                    <div class="text-muted small mt-1">View and manage all tenants</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="#" class="card card-link card-link-pop shadow-sm border-0 rounded-3 text-center py-4 text-decoration-none">
                                    <div class="avatar bg-success-lt mx-auto mb-3"><i class="ti ti-plus"></i></div>
                                    <div class="fw-bold text-body">Create new plan</div>
                                    <div class="text-muted small mt-1">Add a new billing plan</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="#" class="card card-link card-link-pop shadow-sm border-0 rounded-3 text-center py-4 text-decoration-none">
                                    <div class="avatar bg-info-lt mx-auto mb-3"><i class="ti ti-receipt"></i></div>
                                    <div class="fw-bold text-body">Subscriptions</div>
                                    <div class="text-muted small mt-1">Monitor all subscriptions</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="#" class="card card-link card-link-pop shadow-sm border-0 rounded-3 text-center py-4 text-decoration-none">
                                    <div class="avatar bg-warning-lt mx-auto mb-3"><i class="ti ti-external-link"></i></div>
                                    <div class="fw-bold text-body">Visit landing page</div>
                                    <div class="text-muted small mt-1">Preview your public site</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener("DOMContentLoaded", function () {
        // Theme color extraction
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#f1f5f9' : '#333';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        
        // 1. Tenants Line Chart
        const tenantsLabels = {!! json_encode($tenantsCreatedChart['labels'] ?? []) !!};
        const tenantsData = {!! json_encode($tenantsCreatedChart['data'] ?? []) !!};
        
        new ApexCharts(document.getElementById('tenants-chart'), {
            chart: {
                type: 'area',
                fontFamily: 'inherit',
                height: 250,
                parentHeightOffset: 0,
                toolbar: { show: false },
                animations: { enabled: false },
            },
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            stroke: { width: 2, lineCap: "round", curve: "smooth" },
            series: [{ name: "Tenants Created", data: tenantsData }],
            tooltip: { theme: isDark ? 'dark' : 'light' },
            grid: {
                strokeDashArray: 4,
                borderColor: gridColor,
                padding: { top: 0, right: 0, bottom: 0, left: 0 }
            },
            xaxis: {
                categories: tenantsLabels,
                labels: { style: { colors: isDark ? '#9ca3af' : '#64748b' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false }
            },
            yaxis: {
                labels: { style: { colors: isDark ? '#9ca3af' : '#64748b' } }
            },
            colors: ['#2fb344'],
        }).render();

        // 2. Subscription Status Donut Chart
        new ApexCharts(document.getElementById('subscription-status-chart'), {
            chart: {
                type: 'donut',
                fontFamily: 'inherit',
                height: 200,
                sparkline: { enabled: true },
                animations: { enabled: false },
            },
            fill: { opacity: 1 },
            series: [{{ $activeSubscriptions }}, {{ $trialSubscriptions }}],
            labels: ["Active", "Trial"],
            tooltip: { theme: isDark ? 'dark' : 'light' },
            grid: { padding: { top: 20, right: 0, bottom: 0, left: 0 } },
            colors: ['#2fb344', '#206bc4'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            name: { show: true },
                            value: {
                                show: true,
                                fontSize: '24px',
                                fontWeight: 600,
                                color: textColor,
                                formatter: function (val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                color: isDark ? '#9ca3af' : '#64748b',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0);
                                }
                            }
                        }
                    }
                }
            },
            legend: { show: false }
        }).render();

        // 3. Plans Bar Chart
        const planData = {!! json_encode($subscriptionsByPlan) !!};
        const planLabels = planData.map(p => p.name);
        const planValues = planData.map(p => p.count);
        
        new ApexCharts(document.getElementById('plans-chart'), {
            chart: {
                type: 'bar',
                fontFamily: 'inherit',
                height: 250,
                parentHeightOffset: 0,
                toolbar: { show: false },
                animations: { enabled: false },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '40%',
                    borderRadius: 3
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'end',
                style: {
                    colors: ['#fff']
                },
                formatter: function (val) {
                    return val
                },
                offsetX: 0,
            },
            series: [{ name: "Subscriptions", data: planValues }],
            tooltip: { theme: isDark ? 'dark' : 'light' },
            grid: {
                strokeDashArray: 4,
                borderColor: gridColor,
                padding: { top: 0, right: 0, bottom: 0, left: 0 }
            },
            xaxis: {
                categories: planLabels,
                labels: { style: { colors: isDark ? '#9ca3af' : '#64748b' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: { style: { colors: isDark ? '#9ca3af' : '#64748b', fontWeight: 500 } }
            },
            colors: ['#2fb344'],
        }).render();
    });
</script>
@endsection
