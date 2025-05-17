@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="page-title">
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
                <!-- Key Metrics -->
                <div class="row row-deck row-cards mb-4">
                    <!-- Remaining Liability -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar bg-primary-lt text-primary me-3">
                                        <i class="ti ti-building-warehouse"></i>
                                    </div>
                                    <div class="text-body">Remaining Liability</div>
                                    @php
                                        $liabilityPercentage =
                                            $totalliability > 0 ? round(($countliability / $totalliability) * 100) : 0;
                                        $liabilityTrend =
                                            $countliability < $totalliability * 0.5 ? 'positive' : 'negative';
                                    @endphp

                                    @if ($liabilityTrend == 'positive')
                                        <span class="ms-auto badge bg-success-lt">
                                            <i class="ti ti-trending-up me-1"></i> {{ $liabilityPercentage }}%
                                        </span>
                                    @else
                                        <span class="ms-auto badge bg-danger-lt">
                                            <i class="ti ti-trending-down me-1"></i> {{ $liabilityPercentage }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="h2 mb-2">
                                    {{ \App\Helpers\CurrencyHelper::format($countliability) }}
                                </div>
                                <div class="d-flex align-items-center text-muted">
                                    <div class="flex-grow-1">
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-primary" style="width: {{ $liabilityPercentage }}%"
                                                role="progressbar" aria-label="{{ $liabilityPercentage }}% of total">
                                            </div>
                                        </div>
                                    </div>
                                    <span class="ms-2 text-nowrap">
                                        of {{ \App\Helpers\CurrencyHelper::format($totalliability) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Receivable -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar bg-green-lt text-green me-3">
                                        <i class="ti ti-moneybag"></i>
                                    </div>
                                    <div class="text-body">Account Receivable</div>
                                    @php
                                        $receivablePercentage =
                                            $totalRevenue > 0 ? round(($countRevenue / $totalRevenue) * 100) : 0;
                                        $receivableTrend =
                                            $countRevenue < $totalRevenue * 0.5 ? 'negative' : 'positive';
                                    @endphp

                                    @if ($receivableTrend == 'positive')
                                        <span class="ms-auto badge bg-success-lt">
                                            <i class="ti ti-trending-up me-1"></i> {{ $receivablePercentage }}%
                                        </span>
                                    @else
                                        <span class="ms-auto badge bg-danger-lt">
                                            <i class="ti ti-trending-down me-1"></i> {{ $receivablePercentage }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="h2 mb-2">
                                    {{ \App\Helpers\CurrencyHelper::format($countRevenue) }}
                                </div>
                                <div class="d-flex align-items-center text-muted">
                                    <div class="flex-grow-1">
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-green" style="width: {{ $receivablePercentage }}%"
                                                role="progressbar" aria-label="{{ $receivablePercentage }}% of total">
                                            </div>
                                        </div>
                                    </div>
                                    <span class="ms-2 text-nowrap">
                                        of {{ \App\Helpers\CurrencyHelper::format($totalRevenue) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Earnings -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar bg-azure-lt text-azure me-3">
                                        <i class="ti ti-chart-pie"></i>
                                    </div>
                                    <div class="text-body">Monthly Earnings</div>
                                    @php
                                        $monthlyTrend = $countSales > 0 ? 'positive' : 'neutral';
                                    @endphp

                                    @if ($monthlyTrend == 'positive')
                                        <span class="ms-auto badge bg-success-lt">
                                            <i class="ti ti-trending-up me-1"></i> This month
                                        </span>
                                    @else
                                        <span class="ms-auto badge bg-muted-lt">
                                            This month
                                        </span>
                                    @endif
                                </div>
                                <div class="h2 mb-2">
                                    {{ \App\Helpers\CurrencyHelper::format($countSales) }}
                                </div>
                                <div class="d-flex align-items-center text-muted">
                                    <span class="flex-grow-1 text-muted">
                                        This month
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Overdue -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar bg-red-lt text-red me-3">
                                        <i class="ti ti-alert-triangle"></i>
                                    </div>
                                    <div class="text-body">Payment Overdue</div>
                                    @php
                                        $overdueTrend = $outCountUnpaid > 5 ? 'negative' : 'positive';
                                    @endphp

                                    @if ($overdueTrend == 'positive')
                                        <span class="ms-auto badge bg-success-lt">
                                            <i class="ti ti-check me-1"></i> All good
                                        </span>
                                    @else
                                        <span class="ms-auto badge bg-danger-lt">
                                            <i class="ti ti-alert-circle me-1"></i> Action needed
                                        </span>
                                    @endif
                                </div>
                                <div class="h2 mb-2">
                                    {{ $outCountUnpaid }}
                                </div>
                                <div class="d-flex align-items-center text-muted">
                                    <a href="{{ route('admin.sales', ['status' => 'Unpaid']) }}"
                                        class="text-muted text-nowrap">
                                        <i class="ti ti-arrow-right me-1"></i> View details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row">
                    <!-- Left Column (8 columns wide) -->
                    <div class="col-lg-8">
                        <!-- Top Selling Products -->
                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="ti ti-shopping-cart-star fs-3 me-2"></i> Top Selling Products
                                </div>
                                <div class="card-actions">
                                    <a href="" class="btn btn-icon btn-sm" title="More details">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th class="text-center">Units Sold</th>
                                            <th class="text-end">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topSellingProducts as $product)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="avatar avatar-sm me-2 rounded"
                                                            style="background-image: url({{ asset($product->image ?? 'images/placeholder.png') }})"></span>
                                                        <div>
                                                            <div class="font-weight-medium">{{ $product->name }}</div>
                                                            <div class="text-muted small">{{ $product->code }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-azure-lt">{{ $product->units_sold }}</span>
                                                </td>
                                                <td class="text-end font-weight-medium">
                                                    {{ \App\Helpers\CurrencyHelper::format($product->revenue) }}
                                                </td>
                                            </tr>
                                        @endforeach

                                        @if (count($topSellingProducts) == 0)
                                            <tr>
                                                <td colspan="4" class="text-center py-3">
                                                    <span class="text-muted">No products data available</span>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-end">
                                <a href="{{ route('admin.product') }}" class="text-primary border-0">
                                    View all products <i class="ti ti-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-header">
                                <div class="card-title"><i class="ti ti-activity fs-3 me-2 text-primary"></i> Recent
                                    Activity
                                </div>
                                <div class="card-actions">
                                    <div class="dropdown">
                                        <a href="#" class="btn btn-icon" data-bs-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="#" class="dropdown-item">All activities</a>
                                            <a href="#" class="dropdown-item">Sales only</a>
                                            <a href="#" class="dropdown-item">Purchases only</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush">
                                @php
                                    // Combine and sort them by date
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
                                                    <span class="avatar bg-green-lt">
                                                        <i class="ti ti-arrow-narrow-up"></i>
                                                    </span>
                                                @else
                                                    <span class="avatar bg-pink-lt">
                                                        <i class="ti ti-arrow-narrow-down"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="col">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">
                                                            @if ($activity['type'] == 'sale')
                                                                <a href="" class="text-reset">
                                                                    Invoice {{ $activity['data']->invoice }}
                                                                </a>
                                                                to {{ $activity['data']->customer->name ?? 'Customer' }}
                                                            @else
                                                                <a href="" class="text-reset">
                                                                    Purchase {{ $activity['data']->invoice }}
                                                                </a>
                                                                from {{ $activity['data']->supplier->name ?? 'Supplier' }}
                                                            @endif
                                                        </div>
                                                        <div class="text-muted">
                                                            @php
                                                                $statusColor = 'danger';
                                                                if ($activity['data']->status == 'Paid') {
                                                                    $statusColor = 'success';
                                                                } elseif ($activity['data']->status == 'Partial') {
                                                                    $statusColor = 'warning';
                                                                }
                                                            @endphp
                                                            <span class="badge bg-{{ $statusColor }}-lt">
                                                                {{ $activity['data']->status }}
                                                            </span>
                                                            <span
                                                                class="ms-2">{{ \App\Helpers\CurrencyHelper::format($activity['data']->total) }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="text-nowrap text-muted ms-3">
                                                        {{ $activity['date']->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item">
                                        <div class="text-center py-3">
                                            <span class="text-muted">No recent activities found</span>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <div class="card-footer text-center border-top">
                                <a href="" class="btn-link">View all activity <i
                                        class="ti ti-arrow-right ms-1"></i></a>
                            </div>
                        </div>

                        <!-- Key Business Partners -->
                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-header">
                                <div class="card-title"><i class="ti ti-building fs-3 me-2 text-indigo"></i> Key Business
                                    Partners</div>
                            </div>
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                    <li class="nav-item">
                                        <a href="#top-customers" class="nav-link active" data-bs-toggle="tab">
                                            <i class="ti ti-users me-1"></i> Top Customers
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#top-suppliers" class="nav-link" data-bs-toggle="tab">
                                            <i class="ti ti-truck me-1"></i> Top Suppliers
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body tab-content p-0">
                                <!-- Top Customers Tab -->
                                <div class="tab-pane active" id="top-customers">
                                    <div class="list-group list-group-flush">
                                        @forelse ($topCustomers as $customer)
                                            <a href="{{ route('admin.customer', $customer->id) }}"
                                                class="list-group-item">
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="avatar bg-blue-lt me-3">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                                    <div class="flex-fill">
                                                        <div>{{ $customer->name }}</div>
                                                        <div class="text-muted small">
                                                            {{ \App\Helpers\CurrencyHelper::format($customer->total_sales) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="list-group-item">
                                                <div class="text-center py-3">
                                                    <span class="text-muted">No customer data available</span>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Top Suppliers Tab -->
                                <div class="tab-pane" id="top-suppliers">
                                    <div class="list-group list-group-flush">
                                        @forelse ($topSuppliers as $supplier)
                                            <a href="{{ route('admin.supplier', $supplier->id) }}"
                                                class="list-group-item">
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="avatar bg-purple-lt me-3">{{ strtoupper(substr($supplier->name, 0, 1)) }}</span>
                                                    <div class="flex-fill">
                                                        <div>{{ $supplier->name }}</div>
                                                        <div class="text-muted small">
                                                            <span
                                                                class="badge {{ $supplier->location == 'IN' ? 'bg-green-lt' : 'bg-orange-lt' }}">
                                                                {{ $supplier->location }}
                                                            </span>
                                                            {{ \App\Helpers\CurrencyHelper::format($supplier->total_purchases) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="list-group-item">
                                                <div class="text-center py-3">
                                                    <span class="text-muted">No supplier data available</span>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (4 columns wide) -->
                    <div class="col-lg-4">
                        <!-- System Alerts -->
                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-status-top bg-danger"></div>
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-alert-circle fs-3 me-2 text-danger"></i>System
                                    Alerts
                                </h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <!-- Low Stock Alert -->
                                    <div class="col-6">
                                        <div class="card card-sm h-100 bg-red-lt">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-red-lt text-red me-3">
                                                        <i class="ti ti-alert-triangle"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-medium">Low Stock</div>
                                                        <div class="h3 mb-0">{{ $lowStockCount }}</div>
                                                        <div class="text-muted small">
                                                            <a href="{{ route('admin.product', ['low_stock' => 1]) }}"
                                                                class="text-reset">Take action</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Expiring Soon Alert -->
                                    <div class="col-6">
                                        <div class="card card-sm h-100 bg-yellow-lt">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-yellow-lt text-yellow me-3">
                                                        <i class="ti ti-calendar-event"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-medium">Exp Soon</div>
                                                        <div class="h3 mb-0">{{ $expiringSoonCount }}</div>
                                                        <div class="text-muted small">
                                                            <a href="{{ route('admin.product', ['expiring_soon' => 1]) }}"
                                                                class="text-reset">Review items</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-status-top bg-primary"></div>
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="ti ti-currency-dollar fs-3 me-2 text-primary"></i> Financial Summary
                                </div>
                                <div class="card-actions">
                                    <a href="" class="btn btn-icon" title="Financial Reports">
                                        <i class="ti ti-chart-bar"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                @php
                                    $operatingExpenses = $totalliability - $countliability;

                                    $financials = [
                                        [
                                            'label' => 'Total Liabilities',
                                            'value' => $totalliability,
                                            'icon' => 'ti-wallet',
                                            'color' => 'red',
                                        ],
                                        [
                                            'label' => 'This Month Paid Liabilities',
                                            'value' => $liabilitypaymentMonthly,
                                            'icon' => 'ti-calendar',
                                            'color' => 'green',
                                        ],
                                        [
                                            'label' => 'Total Account Receivable',
                                            'value' => $totalRevenue,
                                            'icon' => 'ti-report-money',
                                            'color' => 'blue',
                                        ],
                                        [
                                            'label' => 'This Month Receivable Paid',
                                            'value' => $paidDebtMonthly,
                                            'icon' => 'ti-coin',
                                            'color' => 'green',
                                        ],
                                        [
                                            'label' => 'Operating Expenses',
                                            'value' => $operatingExpenses,
                                            'icon' => 'ti-shopping-cart',
                                            'color' => 'orange',
                                        ],
                                    ];
                                @endphp

                                @foreach ($financials as $item)
                                    <div class="d-flex align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <span
                                            class="avatar avatar-sm bg-{{ $item['color'] }}-lt text-{{ $item['color'] }} me-3">
                                            <i class="ti {{ $item['icon'] }}"></i>
                                        </span>
                                        <div class="flex-fill">{{ $item['label'] }}</div>
                                        <strong>{{ \App\Helpers\CurrencyHelper::format($item['value']) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-status-top bg-azure"></div>
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="ti ti-users fs-3 me-2 text-azure"></i> Customer Insights
                                </div>
                                <div class="card-actions">
                                    <a href="" class="btn btn-icon" title="Customer Management">
                                        <i class="ti ti-settings"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <!-- Customer payment performance -->
                                <div class="p-3 border-bottom">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="text-azure me-2"><i class="ti ti-credit-card"></i></div>
                                        <div class="h4 mb-0">Payment Performance</div>
                                    </div>

                                    <div class="d-flex align-items-baseline mb-1">
                                        <span class="flex-fill text-muted">On-time payments:</span>
                                        <span class="font-weight-medium">{{ round($collectionRate) }}%</span>
                                    </div>
                                    <div class="progress progress-sm mb-3">
                                        <div class="progress-bar bg-success" style="width: {{ round($collectionRate) }}%"
                                            role="progressbar"></div>
                                    </div>

                                    <div class="d-flex align-items-baseline mb-1">
                                        <span class="flex-fill text-muted">Average payment days:</span>
                                        <span class="font-weight-medium">{{ $avgDueDays }} days</span>
                                    </div>
                                    @php
                                        $avgDaysBgColor =
                                            $avgDueDays <= 15
                                                ? 'bg-success'
                                                : ($avgDueDays <= 30
                                                    ? 'bg-warning'
                                                    : 'bg-danger');
                                        $avgDaysPercentage = min(100, max(0, ($avgDueDays / 45) * 100));
                                    @endphp
                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $avgDaysBgColor }}"
                                            style="width: {{ $avgDaysPercentage }}%" role="progressbar"></div>
                                    </div>
                                </div>

                                <!-- Payment terms distribution -->
                                <div class="p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="text-azure me-2"><i class="ti ti-clock"></i></div>
                                        <div class="h4 mb-0">Payment Terms</div>
                                    </div>

                                    @php
                                        // These would come from actual data through controller
                                        $paymentTerms = [
                                            ['term' => 'Net 15', 'count' => 24, 'color' => 'green'],
                                            ['term' => 'Net 30', 'count' => 38, 'color' => 'azure'],
                                            ['term' => 'Net 60', 'count' => 16, 'color' => 'orange'],
                                            ['term' => 'Direct Payment', 'count' => 12, 'color' => 'purple'],
                                        ];
                                        $totalCustomers = array_sum(array_column($paymentTerms, 'count'));
                                    @endphp

                                    @foreach ($paymentTerms as $term)
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="me-2 dot bg-{{ $term['color'] }}"></div>
                                            <div>{{ $term['term'] }}</div>
                                            <div class="ms-auto">
                                                <span class="text-muted">{{ $term['count'] }}</span>
                                                <span class="ms-2 badge bg-{{ $term['color'] }}-lt">
                                                    {{ round(($term['count'] / $totalCustomers) * 100) }}%
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card-footer bg-azure-lt">
                                <div class="row text-center">
                                    <div class="col">
                                        <div class="text-muted">Total Customers</div>
                                        <div class="h3">{{ $totalCustomers }}</div>
                                    </div>
                                    <div class="col">
                                        <div class="text-muted">Active Customers</div>
                                        <div class="h3">{{ round($totalCustomers * 0.75) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Status Summary -->
                        <div class="card mb-4 shadow-sm border-1">
                            <div class="card-status-top bg-green"></div>
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-file-invoice fs-3 me-2 text-green"></i>Invoice
                                    Status
                                </h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex align-items-baseline mb-3">
                                    <div class="h1 mb-0 me-2">{{ ($outCount ?? 0) + ($inCount ?? 0) }}</div>
                                    <div class="me-auto">
                                        <span class="text-muted">Total Invoices</span>
                                    </div>
                                    <span class="badge bg-green-lt ms-auto">
                                        <i class="ti ti-check me-1"></i> {{ round($collectionRate) }}% collection rate
                                    </span>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex mb-2">
                                        <div>
                                            <i class="ti ti-arrow-up text-blue me-1"></i> Outgoing Invoices
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge bg-blue-lt">{{ $outCount ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="progress progress-sm mb-2">
                                        @php
                                            $outPercentage =
                                                ($outCount ?? 0) > 0
                                                    ? ((($outCount ?? 0) - ($outCountUnpaid ?? 0)) / $outCount) * 100
                                                    : 0;
                                        @endphp
                                        <div class="progress-bar bg-blue" style="width: {{ $outPercentage }}%"
                                            role="progressbar"></div>
                                    </div>
                                    <div class="d-flex text-muted small mb-3">
                                        <div>
                                            <i class="ti ti-check me-1 text-success"></i>
                                            {{ ($outCount ?? 0) - ($outCountUnpaid ?? 0) }} paid
                                        </div>
                                        <div class="ms-auto">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ $outCountUnpaid ?? 0 }} awaiting
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex mb-2">
                                        <div>
                                            <i class="ti ti-arrow-down text-pink me-1"></i> Incoming Invoices
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge bg-pink-lt">{{ $inCount ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="progress progress-sm mb-2">
                                        @php
                                            $inPercentage =
                                                ($inCount ?? 0) > 0
                                                    ? ((($inCount ?? 0) - ($inCountUnpaid ?? 0)) / $inCount) * 100
                                                    : 0;
                                        @endphp
                                        <div class="progress-bar bg-pink" style="width: {{ $inPercentage }}%"
                                            role="progressbar"></div>
                                    </div>
                                    <div class="d-flex text-muted small">
                                        <div>
                                            <i class="ti ti-check me-1 text-success"></i>
                                            {{ ($inCount ?? 0) - ($inCountUnpaid ?? 0) }} paid
                                        </div>
                                        <div class="ms-auto">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ $inCountUnpaid ?? 0 }} awaiting
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row text-center">
                                    <div class="col">
                                        <div class="text-muted">Average Due (Days)</div>
                                        <div class="h3">
                                            {{ $avgDueDays }}
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="text-muted">Collection Rate</div>
                                        <div class="h3">
                                            {{ $collectionRate }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-1">
                            <div class="card-status-top bg-secondary"></div>
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-rocket fs-3 me-2"></i>Quick Actions
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <a href="{{ route('admin.sales.create') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-receipt fs-1 mb-2"></i>
                                            <span>New Sale</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('admin.po.create') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-shopping-cart fs-1 mb-2"></i>
                                            <span>New Purchase</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('admin.product.create') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-box fs-1 mb-2"></i>
                                            <span>Add Product</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href=""
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-chart-bar fs-1 mb-2"></i>
                                            <span>Reports</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('admin.pos') }}"
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-cash fs-1 mb-2"></i>
                                            <span>POS</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href=""
                                            class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="ti ti-history fs-1 mb-2"></i>
                                            <span>Activity Log</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endsection
