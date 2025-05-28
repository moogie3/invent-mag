<div class="col-md-4 mb-4">
    <div class="card shadow-sm border-1 h-100">
        <div class="card-status-top bg-azure"></div>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="ti ti-users fs-3 me-2 text-azure"></i> Customer
                Insights</h3>
        </div>
        <div class="card-body p-3">
            <!-- Payment Performance -->
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <div><i class="ti ti-credit-card me-1"></i></div>
                    <div class="h4 mb-0">Payment Performance</div>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">On-time payments:</span>
                    <span class="fw-medium">{{ $customerInsights['collectionRate'] }}%</span>
                </div>
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-success" style="width: {{ $customerInsights['collectionRate'] }}%">
                    </div>
                </div>
            </div>
            <!-- Payment Terms Distribution -->
            <div>
                <div class="d-flex align-items-center mb-2">
                    <div><i class="ti ti-clock me-1"></i></div>
                    <div class="h4 mb-0">Payment Terms</div>
                </div>
                @foreach ($customerInsights['paymentTerms'] as $term)
                    <div class="d-flex align-items-center mb-2">
                        <div class="dot me-2"></div>
                        <div>{{ $term['term'] }}</div>
                        <div class="ms-auto small text-muted">
                            {{ $term['count'] }}
                            ({{ round(($term['count'] / $customerInsights['totalCustomers']) * 100) }}%)
                        </div>
                    </div>
                @endforeach
                <div class="mt-3 row text-center">
                    <div class="col">
                        <div class="small text-muted">Total Customers</div>
                        <div class="h4">{{ $customerInsights['totalCustomers'] }}</div>
                    </div>
                    <div class="col">
                        <div class="small text-muted">Active Customers</div>
                        <div class="h4">{{ $customerInsights['activeCustomers'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
