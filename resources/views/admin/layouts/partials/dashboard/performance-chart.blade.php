<!-- Updated HTML structure for the card -->
<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-chart-area fs-3 me-2"></i> {{ __('messages.performance_analytics') }}
        </h3>
        <div class="card-actions">
            <div class="dropdown">
                <button class="btn btn-sm btn-icon dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="#" data-chart-range="7days" class="dropdown-item chart-range-btn">
                            <i class="ti ti-calendar-week me-2"></i>{{ __('messages.last_7_days') }}
                        </a></li>
                    <li><a href="#" data-chart-range="30days" class="dropdown-item chart-range-btn">
                            <i class="ti ti-calendar-month me-2"></i>{{ __('messages.last_30_days') }}
                        </a></li>
                    <li><a href="#" data-chart-range="3months" class="dropdown-item chart-range-btn">
                            <i class="ti ti-calendar me-2"></i>{{ __('messages.last_3_months') }}
                        </a></li>
                    <li><a href="#" data-chart-range="year" class="dropdown-item chart-range-btn">
                            <i class="ti ti-calendar me-2"></i>{{ __('messages.last_year') }}
                        </a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body" style="min-height: 420px;">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-fill mb-3" id="chartTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-chart"
                    type="button" role="tab" data-chart-type="sales" class="nav-link active chart-type-btn">
                    <i class="ti ti-trending-up me-2"></i>{{ __('messages.sales_performance') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-chart"
                    type="button" role="tab" data-chart-type="purchases" class="nav-link chart-type-btn">
                    <i class="ti ti-shopping-cart me-2"></i>{{ __('messages.purchase_performance') }}
                </button>
            </li>
        </ul>

        <!-- Chart Container -->
        <div class="tab-content" id="chartTabContent">
            <div class="chart-container" style="height: 300px;">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>
</div>
