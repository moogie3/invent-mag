<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-chart-area fs-3 me-2"></i> Performance Analytics
        </h3>
        <div class="card-actions">
            <div class="dropdown">
                <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="updateChart('7days')">Last 7
                        days</a>
                    <a class="dropdown-item" href="#" onclick="updateChart('30days')">Last 30
                        days</a>
                    <a class="dropdown-item" href="#" onclick="updateChart('3months')">Last 3
                        months</a>
                    <a class="dropdown-item" href="#" onclick="updateChart('year')">Last
                        year</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-fill mb-3" id="chartTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-chart"
                    type="button" role="tab" onclick="switchChart('sales')">
                    <i class="ti ti-trending-up me-2"></i>Sales Performance
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-chart"
                    type="button" role="tab" onclick="switchChart('purchases')">
                    <i class="ti ti-shopping-cart me-2"></i>Purchase Performance
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
