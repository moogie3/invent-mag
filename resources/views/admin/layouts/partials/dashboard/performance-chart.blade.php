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
                    <li><a class="dropdown-item" href="#" onclick="updateChart('7days'); return false;">
                            <i class="ti ti-calendar-week me-2"></i>{{ __('messages.last_7_days') }}
                        </a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateChart('30days'); return false;">
                            <i class="ti ti-calendar-month me-2"></i>{{ __('messages.last_30_days') }}
                        </a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateChart('3months'); return false;">
                            <i class="ti ti-calendar me-2"></i>{{ __('messages.last_3_months') }}
                        </a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateChart('year'); return false;">
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
                <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-chart"
                    type="button" role="tab" onclick="switchChart('sales')">
                    <i class="ti ti-trending-up me-2"></i>{{ __('messages.sales_performance') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-chart"
                    type="button" role="tab" onclick="switchChart('purchases')">
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
