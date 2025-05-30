@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        @include('admin.layouts.partials.dashboard.dashboard-header')

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Key Metrics Cards -->
                <div class="row g-4 mb-4">
                    @foreach ($keyMetrics as $metric)
                        @include('admin.layouts.partials.dashboard.metric-card', compact('metric'))
                    @endforeach
                </div>

                <!-- Main Content Section -->
                <div class="row mb-2">
                    <!-- Left Column (8/12) -->
                    <div class="col-lg-8">
                        @include('admin.layouts.partials.dashboard.performance-chart')
                        @include('admin.layouts.partials.dashboard.top-products-table')
                        @include('admin.layouts.partials.dashboard.top-categories-card')
                        @include('admin.layouts.partials.dashboard.recent-transactions')
                    </div>

                    <!-- Right Column (4/12) -->
                    <div class="col-lg-4">
                        @include('admin.layouts.partials.dashboard.system-alerts')
                        @include('admin.layouts.partials.dashboard.quick-actions')
                        @include('admin.layouts.partials.dashboard.analytics-card', [
                            'title' => 'Customer Analysis',
                            'icon' => 'ti-users',
                            'color' => 'azure',
                            'route' => route('admin.customer'),
                            'analytics' => $customerAnalytics,
                            'type' => 'customer',
                        ])
                        @include('admin.layouts.partials.dashboard.analytics-card', [
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
                    @include('admin.layouts.partials.dashboard.revenue-expenses-table')
                    @include('admin.layouts.partials.dashboard.financial-summary')
                    @include('admin.layouts.partials.dashboard.invoice-status')
                    @include('admin.layouts.partials.dashboard.customer-insights')
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.partials.dashboard.dashboard-scripts')
@endsection
