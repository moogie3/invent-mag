@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-dashboard fs-1 me-2"></i> Dashboard
                        </h2>
                        <div class="text-muted mt-1">Business overview and performance metrics</div>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <span class="d-none d-sm-inline">
                                <a href="" class="btn btn-white">
                                    <i class="ti ti-report me-2"></i> Reports
                                </a>
                            </span>
                            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus me-2"></i> New Sale
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Key Metrics Cards -->
                <div class="row g-4 mb-4">
                    @foreach ($keyMetrics as $metric)
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm rounded-3">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                                            style="width: 42px; height: 42px;">
                                            <i class="ti {{ $metric['icon'] }} fs-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $metric['title'] }}</div>
                                        </div>
                                    </div>

                                    <div class="fs-2 fw-bold">
                                        @if ($metric['format'] === 'currency')
                                            {{ \App\Helpers\CurrencyHelper::format($metric['value']) }}
                                        @else
                                            {{ $metric['value'] }}
                                        @endif
                                    </div>

                                    @if ($metric['total'] !== null && $metric['bar_color'])
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar {{ $metric['bar_color'] }}"
                                                style="width: {{ $metric['percentage'] }}%;"></div>
                                        </div>
                                        <div class="text-muted d-flex justify-content-between align-items-center">
                                            <span>
                                                of
                                                @if ($metric['format'] === 'currency')
                                                    {{ \App\Helpers\CurrencyHelper::format($metric['total']) }}
                                                @else
                                                    {{ $metric['total'] }}
                                                @endif
                                            </span>
                                            @if (!$metric['route'])
                                                <span class="badge {{ $metric['badge_class'] }}">
                                                    @if ($metric['trend_icon'])
                                                        <i class="{{ $metric['trend_icon'] }} me-1"></i>
                                                    @endif
                                                    {{ $metric['trend_label'] }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        @if (!$metric['route'])
                                            <div class="text-muted d-flex justify-content-between align-items-center mt-2">
                                                <span class="badge {{ $metric['badge_class'] }}">
                                                    @if ($metric['trend_icon'])
                                                        <i class="{{ $metric['trend_icon'] }} me-1"></i>
                                                    @endif
                                                    {{ $metric['trend_label'] }}
                                                </span>
                                            </div>
                                        @endif
                                    @endif

                                    @if ($metric['route'])
                                        <div class="pt-2 mt-auto d-flex justify-content-between align-items-center">
                                            <a href="{{ $metric['route'] }}" class="text-secondary text-decoration-none">
                                                View details <i class="ti ti-arrow-right ms-1"></i>
                                            </a>
                                            <span class="badge {{ $metric['badge_class'] }}">
                                                @if ($metric['trend_icon'])
                                                    <i class="{{ $metric['trend_icon'] }} me-1"></i>
                                                @endif
                                                {{ $metric['trend_label'] }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Main Content Section -->
                <div class="row mb-4">
                    <!-- Left Column (8/12) -->
                    <div class="col-lg-8">
                        <!-- Top Selling Products -->
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="ti ti-shopping-cart-star fs-3 me-2"></i> Top Selling
                                    Products</h3>
                                <a href="#" class="btn btn-sm btn-icon" title="More details"><i
                                        class="ti ti-dots-vertical"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th class="text-center">Units Sold</th>
                                            <th class="text-end">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topSellingProducts as $product)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="avatar avatar-sm me-2 rounded"
                                                            style="background-image: url({{ asset($product->image ?? 'images/placeholder.png') }})"></span>
                                                        <div>
                                                            <div class="fw-semibold">{{ $product->name }}</div>
                                                            <div class="small text-muted">{{ $product->code }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                                <td class="text-center"><span
                                                        class="badge">{{ $product->units_sold }}</span></td>
                                                <td class="text-end fw-medium">
                                                    {{ \App\Helpers\CurrencyHelper::format($product->revenue) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-3 text-muted">No products data
                                                    available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-end">
                                <a href="{{ route('admin.product') }}" class="text-primary">View all products <i
                                        class="ti ti-arrow-right ms-1"></i></a>
                            </div>
                        </div>

                        <!-- Top Customers and Suppliers -->
                        <div class="row mb-4">
                            <!-- Top Customers -->
                            <div class="col-md-6">
                                <div class="card shadow-sm border-1 h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title mb-0"><i class="ti ti-users fs-3 me-2 text-primary"></i> Top
                                            Customers</h3>
                                        <a href="{{ route('admin.customer') }}" class="btn btn-sm btn-icon"
                                            title="View All Customers"><i class="ti ti-dots-vertical"></i></a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th class="text-end">Total Sales</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($topCustomers as $customer)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold">{{ $customer->name }}</div>
                                                        </td>
                                                        <td class="text-end fw-medium">
                                                            {{ \App\Helpers\CurrencyHelper::format($customer->total_sales) }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-center py-3 text-muted">No customer
                                                            data available</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Suppliers -->
                            <div class="col-md-6">
                                <div class="card shadow-sm border-1 h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title mb-0"><i class="ti ti-truck fs-3 me-2 text-blue"></i> Top
                                            Suppliers</h3>
                                        <a href="{{ route('admin.supplier') }}" class="btn btn-sm btn-icon"
                                            title="View All Suppliers"><i class="ti ti-dots-vertical"></i></a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th>Location</th>
                                                    <th class="text-end">Total Purchases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($topSuppliers as $supplier)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold">{{ $supplier->name }}</div>
                                                        </td>
                                                        <td>{{ $supplier->location }}</td>
                                                        <td class="text-end fw-medium">
                                                            {{ \App\Helpers\CurrencyHelper::format($supplier->total_purchases) }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center py-3 text-muted">No supplier
                                                            data available</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (4/12) -->
                    <div class="col-lg-4">
                        <!-- System Alerts -->
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-status-top bg-danger"></div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="ti ti-alert-circle fs-3 me-2 text-danger"></i>
                                    System Alerts</h3>
                            </div>
                            <div class="card-body p-2">
                                <div class="row g-2">
                                    <!-- Low Stock -->
                                    <div class="col-6">
                                        <div class="card bg-red-lt py-2 px-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="avatar avatar-sm bg-red-lt text-red me-2"><i
                                                        class="ti ti-alert-triangle"></i></div>
                                                <div class="fw-semibold">Low Stock</div>
                                            </div>
                                            <div class="h3 m-0 text-center">{{ $lowStockCount }}</div>
                                            <div class="text-center">
                                                <a href="{{ route('admin.product', ['low_stock' => 1]) }}"
                                                    class="small text-decoration-none">Take action</a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Expiring Soon -->
                                    <div class="col-6">
                                        <div class="card bg-yellow-lt py-2 px-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="avatar avatar-sm bg-yellow-lt text-yellow me-2"><i
                                                        class="ti ti-calendar-event"></i></div>
                                                <div class="fw-semibold">Exp Soon</div>
                                            </div>
                                            <div class="h3 m-0 text-center">{{ $expiringSoonCount }}</div>
                                            <div class="text-center">
                                                <a href="{{ route('admin.product', ['expiring_soon' => 1]) }}"
                                                    class="small text-decoration-none">Review items</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="ti ti-rocket fs-3 me-2"></i> Quick Actions</h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <a href="{{ route('admin.sales.create') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-receipt fs-1 mb-2"></i> <span>New Sale</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('admin.po.create') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-shopping-cart fs-1 mb-2"></i> <span>New Purchase</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('admin.product.create') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-box fs-1 mb-2"></i> <span>Add Product</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="#"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-chart-bar fs-1 mb-2"></i> <span>Reports</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('admin.pos') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-cash fs-1 mb-2"></i> <span>POS</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="#"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-history fs-1 mb-2"></i> <span>Activity Log</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lower Section - Financial Summary, Invoice Status, Customer Insights -->
                <div class="row mb-4">
                    <!-- Financial Summary -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-1 h-100">
                            <div class="card-status-top bg-primary"></div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title"><i class="ti ti-currency-dollar fs-3 me-2"></i> Financial Summary
                                </h3>
                                <a href="#" class="btn btn-sm btn-icon" title="Financial Reports"><i
                                        class="ti ti-chart-bar"></i></a>
                            </div>
                            <div class="card-body p-3">
                                @foreach ($financialItems as $item)
                                    <div class="d-flex align-items-center py-2 border-bottom">
                                        <div class="avatar me-3"><i class="ti {{ $item['icon'] }}"></i></div>
                                        <div class="flex-fill">{{ $item['label'] }}</div>
                                        <div class="fw-semibold">{{ \App\Helpers\CurrencyHelper::format($item['value']) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Status -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-1 h-100">
                            <div class="card-status-top bg-green"></div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="ti ti-file-invoice fs-3 me-2 text-green"></i>
                                    Invoice Status</h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="h1 mb-0">{{ $invoiceStatusData['totalInvoices'] }}</div>
                                    <div class="small text-muted">Total Invoices</div>
                                    <span class="badge bg-green-lt"><i class="ti ti-check"></i>
                                        {{ $invoiceStatusData['collectionRateDisplay'] }}% collection rate</span>
                                </div>
                                <!-- Outgoing Invoices -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <div><i class="ti ti-arrow-up text-blue"></i> Outgoing</div>
                                        <div class="fw-semibold">{{ $invoiceStatusData['outCount'] }}</div>
                                    </div>
                                    <div class="progress progress-sm mb-2">
                                        <div class="progress-bar bg-blue"
                                            style="width: {{ $invoiceStatusData['outPercentage'] }}%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <div>{{ $invoiceStatusData['outCount'] - $invoiceStatusData['outCountUnpaid'] }}
                                            paid</div>
                                        <div>{{ $invoiceStatusData['outCountUnpaid'] }} awaiting</div>
                                    </div>
                                </div>
                                <!-- Incoming Invoices -->
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <div><i class="ti ti-arrow-down text-pink"></i> Incoming</div>
                                        <div class="fw-semibold">{{ $invoiceStatusData['inCount'] }}</div>
                                    </div>
                                    <div class="progress progress-sm mb-2">
                                        <div class="progress-bar bg-pink"
                                            style="width: {{ $invoiceStatusData['inPercentage'] }}%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <div>{{ $invoiceStatusData['inCount'] - $invoiceStatusData['inCountUnpaid'] }} paid
                                        </div>
                                        <div>{{ $invoiceStatusData['inCountUnpaid'] }} awaiting</div>
                                    </div>
                                </div>
                                <!-- Summary -->
                                <div class="mt-3 row text-center">
                                    <div class="col">
                                        <div class="small text-muted">Average Due (Days)</div>
                                        <div class="h3">{{ $invoiceStatusData['avgDueDays'] }}</div>
                                    </div>
                                    <div class="col">
                                        <div class="small text-muted">Collection Rate</div>
                                        <div class="h3">{{ $invoiceStatusData['collectionRate'] }}%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Insights -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-1 h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="ti ti-users fs-3 me-2 text-azure"></i> Customer
                                    Insights</h3>
                                <a href="#" class="btn btn-sm btn-icon" title="Customer Management"><i
                                        class="ti ti-settings"></i></a>
                            </div>
                            <div class="card-body p-3">
                                <!-- Payment Performance -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="me-2"><i class="ti ti-credit-card"></i></div>
                                        <div class="h4 mb-0">Payment Performance</div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">On-time payments:</span>
                                        <span class="fw-medium">{{ $customerInsights['collectionRate'] }}%</span>
                                    </div>
                                    <div class="progress progress-sm mb-2">
                                        <div class="progress-bar bg-success"
                                            style="width: {{ $customerInsights['collectionRate'] }}%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Average payment days:</span>
                                        <span class="fw-medium">{{ $customerInsights['avgDueDays'] }} days</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $customerInsights['bgColor'] }}"
                                            style="width: {{ $customerInsights['percentage'] }}%;"></div>
                                    </div>
                                </div>
                                <!-- Payment Terms Distribution -->
                                <div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-2"><i class="ti ti-clock me-2"></i></div>
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
                </div>

                <!-- Recent Activity -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="ti ti-activity fs-3 me-2 text-primary"></i> Recent
                                    Activity</h3>
                                <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
                            </div>
                            <div class="list-group list-group-flush">
                                @php
                                    $recentActivities = collect();
                                    foreach ($recentSales as $sale) {
                                        $recentActivities->push([
                                            'type' => 'sale',
                                            'data' => $sale,
                                            'date' => $sale->created_at,
                                        ]);
                                    }
                                    foreach ($recentPurchases as $purchase) {
                                        $recentActivities->push([
                                            'type' => 'purchase',
                                            'data' => $purchase,
                                            'date' => $purchase->created_at,
                                        ]);
                                    }
                                    $recentActivities = $recentActivities->sortByDesc('date')->take(5);
                                @endphp
                                @forelse ($recentActivities as $activity)
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                @if ($activity['type'] == 'sale')
                                                    <span class="avatar bg-green-lt"><i
                                                            class="ti ti-arrow-narrow-up"></i></span>
                                                @else
                                                    <span class="avatar bg-pink-lt"><i
                                                            class="ti ti-arrow-narrow-down"></i></span>
                                                @endif
                                            </div>
                                            <div class="col">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        @if ($activity['type'] == 'sale')
                                                            <a href="#" class="text-reset fw-semibold">Invoice
                                                                {{ $activity['data']->invoice }}</a> to
                                                            {{ $activity['data']->customer->name ?? 'Customer' }}
                                                        @else
                                                            <a href="#" class="text-reset fw-semibold">Purchase
                                                                {{ $activity['data']->invoice }}</a> from
                                                            {{ $activity['data']->supplier->name ?? 'Supplier' }}
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted">{{ $activity['date']->diffForHumans() }}
                                                    </div>
                                                </div>
                                                <div class="mt-1 small">
                                                    <span
                                                        class="badge bg-{{ $activity['data']->status == 'Paid' ? 'success' : ($activity['data']->status == 'Partial' ? 'warning' : 'danger') }}-lt">{{ $activity['data']->status }}</span>
                                                    <span
                                                        class="ms-2">{{ \App\Helpers\CurrencyHelper::format($activity['data']->total) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item text-center py-3 text-muted">No recent activities found
                                    </div>
                                @endforelse
                            </div>
                            <div class="card-footer text-center">
                                <a href="#" class="btn-link">View all activity <i
                                        class="ti ti-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
