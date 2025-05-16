@extends('admin.layouts.base')

@section('title', 'Dashboard')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">
                            <i class="ti ti-dashboard    me-2"></i> Dashboard
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <!-- Metrics Cards -->
                <div class="row row-cards mb-4">
                    @php
                        $metrics = [
                            [
                                'icon' => 'ti-building-warehouse',
                                'label' => 'Remaining Liability',
                                'value' => $countliability,
                                'total' => $totalliability,
                                'color' => 'primary',
                                'format' => true,
                            ],
                            [
                                'icon' => 'ti-moneybag',
                                'label' => 'Account Receivable',
                                'value' => $countRevenue,
                                'total' => $totalRevenue,
                                'color' => 'green',
                                'format' => true,
                            ],
                            [
                                'icon' => 'ti-chart-pie',
                                'label' => 'Monthly Earnings',
                                'value' => $countSales,
                                'badge' => 'This month',
                                'color' => 'azure',
                                'format' => true,
                            ],
                            [
                                'icon' => 'ti-alert-triangle',
                                'label' => 'Low Stock Items',
                                'value' => $lowStockCount,
                                'link' => route('admin.product', ['low_stock' => 1]),
                                'color' => 'red',
                                'format' => false,
                            ],
                        ];
                    @endphp

                    @foreach ($metrics as $metric)
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm shadow-sm border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar bg-{{ $metric['color'] }}-light me-3">
                                            <i class="ti {{ $metric['icon'] }}"></i>
                                        </div>
                                        <div>{{ $metric['label'] }}</div>
                                    </div>
                                    <div class="h3 mb-1">
                                        @if ($metric['format'])
                                            {{ \App\Helpers\CurrencyHelper::format($metric['value']) }}
                                        @else
                                            {{ $metric['value'] }}
                                        @endif
                                    </div>
                                    <div class="text-muted text-end">
                                        @if (isset($metric['total']) && $metric['total'] > 0)
                                            <span class="badge bg-{{ $metric['color'] }}-light">
                                                {{ round(($metric['value'] / $metric['total']) * 100) }}% of total
                                            </span>
                                        @elseif (isset($metric['total']))
                                            <span class="badge bg-{{ $metric['color'] }}-light">
                                                0% of total
                                            </span>
                                        @elseif (isset($metric['badge']))
                                            <span
                                                class="badge bg-{{ $metric['color'] }}-light">{{ $metric['badge'] }}</span>
                                        @elseif (isset($metric['link']))
                                            <a href="{{ $metric['link'] }}" class="text-muted">View items</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Main Section -->
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Top Selling Products -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-shopping-cart-star me-2"></i> Top Selling Products
                                </h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topSellingProducts as $product)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="avatar avatar-sm me-2"
                                                            style="background-image: url({{ $product->image }})"></span>
                                                        <div>
                                                            <div>{{ $product->name }}</div>
                                                            <div class="text-muted small">{{ $product->code }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                                <td>{{ $product->units_sold }}</td>
                                                <td>{{ \App\Helpers\CurrencyHelper::format($product->revenue) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-activity me-2"></i> Recent Activity</h3>
                                <div class="card-actions">
                                    <div class="dropdown">
                                        <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown"
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
                                    // This would ideally come from the controller with actual recent activities
                                    $recentSales = \App\Models\Sales::with('customer')
                                        ->orderBy('created_at', 'desc')
                                        ->limit(3)
                                        ->get();

                                    $recentPurchases = \App\Models\Purchase::with('supplier')
                                        ->orderBy('created_at', 'desc')
                                        ->limit(3)
                                        ->get();

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

                                @foreach ($recentActivities as $activity)
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                @if ($activity['type'] == 'sale')
                                                    <span class="avatar bg-green-lt">
                                                        <i class="ti ti-arrow-narrow-up"></i>
                                                    </span>
                                                @else
                                                    <span class="avatar bg-red-lt">
                                                        <i class="ti ti-arrow-narrow-down"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="col">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">
                                                            @if ($activity['type'] == 'sale')
                                                                Invoice {{ $activity['data']->invoice }} to
                                                                {{ $activity['data']->customer->name ?? 'Customer' }}
                                                            @else
                                                                Purchase {{ $activity['data']->invoice }} from
                                                                {{ $activity['data']->supplier->name ?? 'Supplier' }}
                                                            @endif
                                                        </div>
                                                        <div class="text-muted">
                                                            @if ($activity['type'] == 'sale')
                                                                {{ $activity['data']->status }} •
                                                                {{ \App\Helpers\CurrencyHelper::format($activity['data']->total) }}
                                                            @else
                                                                {{ $activity['data']->status }} •
                                                                {{ \App\Helpers\CurrencyHelper::format($activity['data']->total) }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-nowrap text-muted ms-3">
                                                        {{ $activity['date']->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="card-footer">
                                <a href="#" class="btn btn-link">View all activity</a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-4">
                        <!-- Financial Summary -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-currency-dollar me-2"></i> Financial Summary
                                </h3>
                            </div>
                            <div class="card-body">
                                @php
                                    $operatingExpenses = $totalliability - $countliability;

                                    $financials = [
                                        ['label' => 'Total Liabilities', 'value' => $totalliability],
                                        ['label' => 'This Month Paid Liabilities', 'value' => $liabilitypaymentMonthly],
                                        ['label' => 'Total Account Receivable', 'value' => $totalRevenue],
                                        ['label' => 'This Month Receivable Paid', 'value' => $paidDebtMonthly],
                                        ['label' => 'Operating Expenses', 'value' => $operatingExpenses],
                                    ];
                                @endphp
                                @foreach ($financials as $item)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ $item['label'] }}</span>
                                        <strong>{{ \App\Helpers\CurrencyHelper::format($item['value']) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Top Customers and Suppliers -->
                        <div class="card mb-4 shadow-sm border-0">
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
                            <div class="card-body tab-content">
                                <div class="tab-pane active" id="top-customers">
                                    <div class="list-group list-group-flush">
                                        @foreach ($topCustomers as $customer)
                                            <a href="{{ route('admin.customer', $customer->id) }}" class="list-group-item">
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
                                        @endforeach
                                    </div>
                                </div>
                                <div class="tab-pane" id="top-suppliers">
                                    <div class="list-group list-group-flush">
                                        @foreach ($topSuppliers as $supplier)
                                            <a href="{{ route('admin.supplier', $supplier->id) }}"
                                                class="list-group-item">
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="avatar bg-purple-lt me-3">{{ strtoupper(substr($supplier->name, 0, 1)) }}</span>
                                                    <div class="flex-fill">
                                                        <div>{{ $supplier->name }}</div>
                                                        <div class="text-muted small">
                                                            <span
                                                                class="badge {{ $supplier->location == 'IN' ? 'bg-green-lt' : 'bg-orange-lt' }}">{{ $supplier->location }}</span>
                                                            {{ \App\Helpers\CurrencyHelper::format($supplier->total_purchases) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Status Summary -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-chart-dots me-2"></i>Invoice Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2">{{ ($outCount ?? 0) + ($inCount ?? 0) }}</div>
                                    <div class="me-auto">
                                        <span class="text-muted">Total Invoices</span>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex mb-2">
                                        <div>Outgoing Invoices</div>
                                        <div class="ms-auto">
                                            <span class="text-muted">{{ $outCount ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="progress progress-sm">
                                        @php
                                            $outPercentage =
                                                ($outCount ?? 0) > 0 ? (($outCountUnpaid ?? 0) / $outCount) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar bg-primary" style="width: {{ $outPercentage }}%"
                                            role="progressbar">
                                        </div>
                                    </div>
                                    <div class="d-flex mt-2 mb-3">
                                        <div class="text-muted"><i class="ti ti-clock me-1"></i>
                                            {{ $outCountUnpaid ?? 0 }}
                                            awaiting payment</div>
                                        <div class="ms-auto">
                                            <span class="text-success"><i class="ti ti-check me-1"></i>
                                                {{ ($outCount ?? 0) - ($outCountUnpaid ?? 0) }} paid</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex mb-2">
                                        <div>Incoming Invoices</div>
                                        <div class="ms-auto">
                                            <span class="text-muted">{{ $inCount ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="progress progress-sm">
                                        @php
                                            $inPercentage =
                                                ($inCount ?? 0) > 0 ? (($inCountUnpaid ?? 0) / $inCount) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar bg-green" style="width: {{ $inPercentage }}%"
                                            role="progressbar">
                                        </div>
                                    </div>
                                    <div class="d-flex mt-2">
                                        <div class="text-muted"><i class="ti ti-clock me-1"></i>
                                            {{ $inCountUnpaid ?? 0 }}
                                            awaiting payment</div>
                                        <div class="ms-auto">
                                            <span class="text-success"><i class="ti ti-check me-1"></i>
                                                {{ ($inCount ?? 0) - ($inCountUnpaid ?? 0) }} paid</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row text-center">
                                    <div class="col">
                                        <div class="text-muted">Average Due (Days)</div>
                                        <div class="h3">15</div>
                                    </div>
                                    <div class="col">
                                        <div class="text-muted">Collection Rate</div>
                                        <div class="h3">78%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Right Column -->
                </div> <!-- End Main Row -->
            </div> <!-- End Container -->
        </div> <!-- End Page Body -->
    </div> <!-- End Page Wrapper -->
@endsection
