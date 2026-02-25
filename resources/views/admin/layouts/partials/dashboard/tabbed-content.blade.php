<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
            <li class="nav-item">
                <a href="#overview-tab" class="nav-link active fw-bold fs-4" data-bs-toggle="tab">{{ __('messages.overview') }}</a>
            </li>
            <li class="nav-item">
                <a href="#sales-products-tab" class="nav-link fw-bold fs-4" data-bs-toggle="tab">{{ __('messages.sales_products') }}</a>
            </li>
            <li class="nav-item">
                <a href="#analysis-reports-tab" class="nav-link fw-bold fs-4" data-bs-toggle="tab">{{ __('messages.analysis_reports') }}</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <!-- Overview Tab -->
            <div class="tab-pane active" id="overview-tab">
                @include('admin.layouts.partials.dashboard.performance-chart')
            </div>
            <!-- Sales & Products Tab -->
            <div class="tab-pane" id="sales-products-tab">
                @include('admin.layouts.partials.dashboard.top-products-table')
                @include('admin.layouts.partials.dashboard.top-categories-card')
                @include('admin.layouts.partials.dashboard.recent-transactions')
            </div>
            <!-- Analysis & Reports Tab -->
            <div class="tab-pane" id="analysis-reports-tab">
                <div class="row mb-4">
                    <div class="col-12">
                        @include('admin.layouts.partials.dashboard.revenue-expenses-table')
                    </div>
                    <div class="col-12">
                        @include('admin.layouts.partials.dashboard.financial-summary')
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        @include('admin.layouts.partials.dashboard.invoice-status')
                    </div>
                    <div class="col-md-6">
                        @include('admin.layouts.partials.dashboard.customer-insights')
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        @include('admin.layouts.partials.dashboard.analytics-card', [
                            'title' => __('messages.customer_analysis'),
                            'icon' => 'ti-users',
                            'color' => 'azure',
                            'route' => route('admin.customer'),
                            'analytics' => $customerAnalytics,
                            'type' => 'customer',
                        ])
                    </div>
                    <div class="col-md-6">
                        @include('admin.layouts.partials.dashboard.analytics-card', [
                            'title' => __('messages.supplier_analysis'),
                            'icon' => 'ti-truck',
                            'color' => 'purple',
                            'route' => route('admin.supplier'),
                            'analytics' => $supplierAnalytics,
                            'type' => 'supplier',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>