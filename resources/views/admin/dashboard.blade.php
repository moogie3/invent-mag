@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        @include('admin.partials.dashboard.dashboard-header')

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Key Metrics Cards -->
                <div class="row g-4 mb-4">
                    @foreach ($keyMetrics as $metric)
                        @include('admin.partials.dashboard.metric-card', compact('metric'))
                    @endforeach
                </div>

                <!-- Main Content Section -->
                <div class="row mb-4">
                    <!-- Left Column (8/12) -->
                    <div class="col-lg-8">
                        @include('admin.partials.dashboard.performance-chart')
                        @include('admin.partials.dashboard.top-products-table')
                        @include('admin.partials.dashboard.top-categories-card')
                        @include('admin.partials.dashboard.recent-transactions')
                    </div>

                    <!-- Right Column (4/12) -->
                    <div class="col-lg-4">
                        @include('admin.partials.dashboard.system-alerts')
                        @include('admin.partials.dashboard.quick-actions')
                        @include('admin.partials.dashboard.analytics-card', [
                            'title' => 'Customer Analysis',
                            'icon' => 'ti-users',
                            'color' => 'azure',
                            'route' => route('admin.customer'),
                            'analytics' => $customerAnalytics,
                            'type' => 'customer',
                        ])
                        @include('admin.partials.dashboard.analytics-card', [
                            'title' => 'Supplier Analysis',
                            'icon' => 'ti-truck',
                            'color' => 'purple',
                            'route' => route('admin.supplier'),
                            'analytics' => $supplierAnalytics,
                            'type' => 'supplier',
                        ])
                    </div>
                </div>

                <!-- Lower Section -->
                <div class="row mb-4">
                    @include('admin.partials.dashboard.financial-summary')
                    @include('admin.partials.dashboard.invoice-status')
                    @include('admin.partials.dashboard.customer-insights')
                    @include('admin.partials.dashboard.revenue-expenses-table')
                </div>
            </div>
        </div>
    </div>

    @include('admin.partials.dashboard.filter-modal')
    @include('admin.partials.dashboard.dashboard-scripts')
@endsection
