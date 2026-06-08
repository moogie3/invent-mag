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
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 rounded-4 stat-card stat-card-primary card-hover" style="box-shadow: 0 1px 8px rgba(0,0,0,0.07);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-muted" style="font-size:0.68rem;letter-spacing:0.06em;">TOTAL TENANTS</div>
                                <div class="ms-auto">
                                    <div class="avatar bg-primary-lt avatar-sm rounded-3"><i class="ti ti-building-community text-primary"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-2 fw-bold">{{ $totalTenants }}</div>
                            <div class="d-flex mb-0">
                                <a href="{{ route('superadmin.tenants.index') }}" class="text-primary text-decoration-none fw-semibold small">View all tenants <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                        <i class="ti ti-building-community stat-icon text-primary"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 rounded-4 stat-card stat-card-success card-hover" style="box-shadow: 0 1px 8px rgba(0,0,0,0.07);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-muted" style="font-size:0.68rem;letter-spacing:0.06em;">ACTIVE / TRIAL</div>
                                <div class="ms-auto">
                                    <div class="avatar bg-success-lt avatar-sm rounded-3"><i class="ti ti-circle-check text-success"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-2 fw-bold">{{ $activeTenants }}</div>
                            <div class="d-flex mb-0">
                                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-success text-decoration-none fw-semibold small">View subscriptions <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                        <i class="ti ti-circle-check stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 rounded-4 stat-card stat-card-info card-hover" style="box-shadow: 0 1px 8px rgba(0,0,0,0.07);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-muted" style="font-size:0.68rem;letter-spacing:0.06em;">ACTIVE PLANS</div>
                                <div class="ms-auto">
                                    <div class="avatar bg-info-lt avatar-sm rounded-3"><i class="ti ti-packages text-info"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-2 fw-bold">{{ $activePlans }}</div>
                            <div class="d-flex mb-0">
                                <a href="{{ route('superadmin.plans.index') }}" class="text-info text-decoration-none fw-semibold small">Manage plans <i class="ti ti-arrow-right"></i></a>
                            </div>
                        </div>
                        <i class="ti ti-packages stat-icon text-info"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 rounded-4 stat-card stat-card-warning card-hover" style="box-shadow: 0 1px 8px rgba(0,0,0,0.07);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-muted" style="font-size:0.68rem;letter-spacing:0.06em;">TOTAL SUBSCRIPTIONS</div>
                                <div class="ms-auto">
                                    <div class="avatar bg-warning-lt avatar-sm rounded-3"><i class="ti ti-receipt text-warning"></i></div>
                                </div>
                            </div>
                            <div class="h1 mb-2 fw-bold">{{ $totalSubscriptions }}</div>
                            <div class="d-flex mb-0 gap-2">
                                <span class="text-success fw-semibold small">{{ $activeSubscriptions }} active</span>
                                <span class="text-muted small">&middot; {{ $trialSubscriptions }} trial</span>
                            </div>
                        </div>
                        <i class="ti ti-receipt stat-icon text-warning"></i>
                    </div>
                </div>
            </div>
            
            <div class="row row-deck row-cards mb-4">
                <div class="col-lg-8">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                            <h3 class="card-title"><i class="ti ti-trending-up me-2 text-muted"></i>Tenants created (last 6 months)</h3>
                            <div class="card-actions">
                                <span class="badge bg-secondary text-white">Last 6 months</span>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-0">
                            <div id="tenants-chart" style="min-height: 250px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow border-0 rounded-4">
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
                <div class="col-lg-6">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                            <h3 class="card-title"><i class="ti ti-chart-bar me-2 text-muted"></i>Subscriptions by plan (top 5)</h3>
                            <div class="card-actions">
                                <span class="badge bg-secondary text-white">Top 5</span>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-0">
                            <div id="plans-chart" style="min-height: 250px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow border-0 rounded-4 bg-transparent">
                        <div class="card-header border-0 pb-2 pt-0 px-0">
                            <h3 class="card-title"><i class="ti ti-bolt me-2 text-muted"></i>Quick actions</h3>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="{{ route('superadmin.tenants.index') }}" class="card card-link card-link-pop shadow border-0 rounded-4 text-center py-4 text-decoration-none card-hover">
                                    <div class="avatar bg-primary-lt mx-auto mb-3"><i class="ti ti-building-community"></i></div>
                                    <div class="fw-bold text-body">Manage tenants</div>
                                    <div class="text-muted small mt-1">View and manage all tenants</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('superadmin.plans.create') }}" class="card card-link card-link-pop shadow border-0 rounded-4 text-center py-4 text-decoration-none card-hover">
                                    <div class="avatar bg-success-lt mx-auto mb-3"><i class="ti ti-plus"></i></div>
                                    <div class="fw-bold text-body">Create new plan</div>
                                    <div class="text-muted small mt-1">Add a new billing plan</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('superadmin.subscriptions.index') }}" class="card card-link card-link-pop shadow border-0 rounded-4 text-center py-4 text-decoration-none card-hover">
                                    <div class="avatar bg-info-lt mx-auto mb-3"><i class="ti ti-receipt"></i></div>
                                    <div class="fw-bold text-body">Subscriptions</div>
                                    <div class="text-muted small mt-1">Monitor all subscriptions</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('superadmin.billing.index') }}" class="card card-link card-link-pop shadow border-0 rounded-4 text-center py-4 text-decoration-none card-hover">
                                    <div class="avatar bg-warning-lt mx-auto mb-3"><i class="ti ti-currency-dollar"></i></div>
                                    <div class="fw-bold text-body">Billing overview</div>
                                    <div class="text-muted small mt-1">View revenue and orders</div>
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
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#f1f5f9' : '#333';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        
        const tenantsLabels = {!! json_encode($tenantsCreatedChart['labels'] ?? []) !!};
        const tenantsData = {!! json_encode($tenantsCreatedChart['data'] ?? []) !!};
        
        new ApexCharts(document.getElementById('tenants-chart'), {
            chart: { type: 'area', fontFamily: 'inherit', height: 250, parentHeightOffset: 0, toolbar: { show: false }, animations: { enabled: false } },
            dataLabels: { enabled: false },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
            stroke: { width: 2, lineCap: "round", curve: "smooth" },
            series: [{ name: "Tenants Created", data: tenantsData }],
            tooltip: { theme: isDark ? 'dark' : 'light' },
            grid: { strokeDashArray: 4, borderColor: gridColor, padding: { top: 0, right: 0, bottom: 0, left: 0 } },
            xaxis: { categories: tenantsLabels, labels: { style: { colors: isDark ? '#9ca3af' : '#64748b' } }, axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false } },
            yaxis: { labels: { style: { colors: isDark ? '#9ca3af' : '#64748b' } } },
            colors: ['#2fb344'],
        }).render();

        new ApexCharts(document.getElementById('subscription-status-chart'), {
            chart: { type: 'donut', fontFamily: 'inherit', height: 200, sparkline: { enabled: true }, animations: { enabled: false } },
            fill: { opacity: 1 },
            series: [{{ $activeSubscriptions }}, {{ $trialSubscriptions }}],
            labels: ["Active", "Trial"],
            tooltip: { theme: isDark ? 'dark' : 'light' },
            grid: { padding: { top: 20, right: 0, bottom: 0, left: 0 } },
            colors: ['#2fb344', '#206bc4'],
            plotOptions: { pie: { donut: { size: '75%', labels: { show: true, name: { show: true }, value: { show: true, fontSize: '24px', fontWeight: 600, color: textColor, formatter: function (val) { return val } }, total: { show: true, label: 'Total', color: isDark ? '#9ca3af' : '#64748b', formatter: function (w) { return w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0) } } } } } },
            legend: { show: false }
        }).render();

        const planData = {!! json_encode($subscriptionsByPlan) !!};
        const planLabels = planData.map(p => p.name);
        const planValues = planData.map(p => p.count);
        
        new ApexCharts(document.getElementById('plans-chart'), {
            chart: { type: 'bar', fontFamily: 'inherit', height: 250, parentHeightOffset: 0, toolbar: { show: false }, animations: { enabled: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '40%', borderRadius: 3 } },
            dataLabels: { enabled: true, textAnchor: 'end', style: { colors: ['#fff'] }, formatter: function (val) { return val }, offsetX: 0 },
            series: [{ name: "Subscriptions", data: planValues }],
            tooltip: { theme: isDark ? 'dark' : 'light' },
            grid: { strokeDashArray: 4, borderColor: gridColor, padding: { top: 0, right: 0, bottom: 0, left: 0 } },
            xaxis: { categories: planLabels, labels: { style: { colors: isDark ? '#9ca3af' : '#64748b' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { colors: isDark ? '#9ca3af' : '#64748b', fontWeight: 500 } } },
            colors: ['#2fb344'],
        }).render();
    });
</script>
@endSection