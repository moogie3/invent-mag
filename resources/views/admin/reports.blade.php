@extends('admin.layouts.base')

@section('title', 'Reports')

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-chart-bar fs-1 me-2"></i> Reports & Analytics
                        </h2>
                        <div class="text-muted mt-1">Comprehensive business reports and performance analysis</div>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <span class="d-none d-sm-inline">
                                <a href="#" class="btn btn-white" data-bs-toggle="modal"
                                    data-bs-target="#filterModal">
                                    <i class="ti ti-filter me-2"></i> Filter
                                </a>
                            </span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="ti ti-download me-2"></i> Export
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#"><i class="ti ti-file-type-pdf me-2"></i> Export
                                        as PDF</a>
                                    <a class="dropdown-item" href="#"><i class="ti ti-file-type-csv me-2"></i> Export
                                        as CSV</a>
                                    <a class="dropdown-item" href="#"><i class="ti ti-printer me-2"></i> Print
                                        Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Report Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-3">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-primary-lt"
                                        style="width: 42px; height: 42px;">
                                        <i class="ti ti-chart-line fs-2 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Total Revenue</div>
                                    </div>
                                </div>
                                <div class="fs-2 fw-bold">
                                    {{ \App\Helpers\CurrencyHelper::format($totalRevenue ?? 0) }}
                                </div>
                                <div class="text-muted d-flex justify-content-between align-items-center">
                                    <span>This period</span>
                                    <span class="badge bg-success-lt">
                                        <i class="ti ti-trending-up me-1"></i> +12.5%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-3">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-green-lt"
                                        style="width: 42px; height: 42px;">
                                        <i class="ti ti-shopping-cart fs-2 text-green"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Total Sales</div>
                                    </div>
                                </div>
                                <div class="fs-2 fw-bold">{{ $totalSalesCount ?? 0 }}</div>
                                <div class="text-muted d-flex justify-content-between align-items-center">
                                    <span>Transactions</span>
                                    <span class="badge bg-success-lt">
                                        <i class="ti ti-trending-up me-1"></i> +8.2%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-3">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-red-lt"
                                        style="width: 42px; height: 42px;">
                                        <i class="ti ti-building-warehouse fs-2 text-red"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Total Purchases</div>
                                    </div>
                                </div>
                                <div class="fs-2 fw-bold">
                                    {{ \App\Helpers\CurrencyHelper::format($totalPurchases ?? 0) }}
                                </div>
                                <div class="text-muted d-flex justify-content-between align-items-center">
                                    <span>Expenses</span>
                                    <span class="badge bg-warning-lt">
                                        <i class="ti ti-trending-down me-1"></i> -3.1%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-3">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-blue-lt"
                                        style="width: 42px; height: 42px;">
                                        <i class="ti ti-calculator fs-2 text-blue"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Net Profit</div>
                                    </div>
                                </div>
                                <div class="fs-2 fw-bold">
                                    {{ \App\Helpers\CurrencyHelper::format(($totalRevenue ?? 0) - ($totalPurchases ?? 0)) }}
                                </div>
                                <div class="text-muted d-flex justify-content-between align-items-center">
                                    <span>Profit margin</span>
                                    <span class="badge bg-success-lt">
                                        <i class="ti ti-trending-up me-1"></i> +15.3%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Section -->
                <div class="row mb-4">
                    <!-- Left Column (8/12) -->
                    <div class="col-lg-8">

                        <!-- Revenue vs Expenses -->

                    </div>

                    <!-- Right Column (4/12) -->
                    <div class="col-lg-4">
                        <!-- Payment Status Overview -->
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-status-top bg-green"></div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="ti ti-credit-card fs-3 me-2 text-green"></i>Sales Payment Status
                                </h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <div class="card bg-success-lt py-2 px-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="avatar avatar-sm bg-success-lt text-success me-2">
                                                    <i class="ti ti-check"></i>
                                                </div>
                                                <div class="fw-semibold">Paid</div>
                                            </div>
                                            <div class="h3 m-0 text-center">{{ $paidCount ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-warning-lt py-2 px-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="avatar avatar-sm bg-warning-lt text-warning me-2">
                                                    <i class="ti ti-clock"></i>
                                                </div>
                                                <div class="fw-semibold">Pending</div>
                                            </div>
                                            <div class="h3 m-0 text-center">{{ $pendingCount ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="card bg-danger-lt py-2 px-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="avatar avatar-sm bg-danger-lt text-danger me-2">
                                                    <i class="ti ti-x"></i>
                                                </div>
                                                <div class="fw-semibold">Overdue</div>
                                            </div>
                                            <div class="h3 m-0 text-center">{{ $overdueCount ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-blue-lt py-2 px-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <div class="avatar avatar-sm bg-blue-lt text-blue me-2">
                                                    <i class="ti ti-percentage"></i>
                                                </div>
                                                <div class="fw-semibold">Partial</div>
                                            </div>
                                            <div class="h3 m-0 text-center">{{ $partialCount ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Performing Categories -->
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="ti ti-category fs-3 me-2 text-blue"></i> Top Categories
                                </h3>
                            </div>
                            <div class="card-body p-3">
                                @forelse ($topCategories ?? [] as $category)
                                    <div class="d-flex align-items-center py-2">
                                        <div class="avatar me-3 bg-{{ $loop->iteration % 2 ? 'primary' : 'success' }}-lt">
                                            <i class="ti ti-tag"></i>
                                        </div>
                                        <div class="flex-fill">
                                            <div class="fw-semibold">{{ $category['name'] }}</div>
                                            <div class="small text-muted">{{ $category['products_count'] }} products</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold">
                                                {{ \App\Helpers\CurrencyHelper::format($category['revenue']) }}</div>
                                            <div class="small text-muted">{{ $category['percentage'] }}%</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-3 text-muted">No category data available</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Quick Report Actions -->
                        <div class="card shadow-sm border-1 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="ti ti-bolt fs-3 me-2"></i> Quick Reports
                                </h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="#"
                                            class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-between">
                                            <span><i class="ti ti-calendar me-2"></i> Daily Sales Report</span>
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="#"
                                            class="btn btn-outline-success w-100 d-flex align-items-center justify-content-between">
                                            <span><i class="ti ti-chart-pie me-2"></i> Profit & Loss</span>
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="#"
                                            class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-between">
                                            <span><i class="ti ti-users me-2"></i> Customer Report</span>
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="#"
                                            class="btn btn-outline-info w-100 d-flex align-items-center justify-content-between">
                                            <span><i class="ti ti-package me-2"></i> Inventory Report</span>
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="#"
                                            class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-between">
                                            <span><i class="ti ti-file-invoice me-2"></i> Tax Report</span>
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal modal-blur fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Reports</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date Range</label>
                                <select class="form-select" name="date_range">
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="last_week">Last Week</option>
                                    <option value="this_month" selected>This Month</option>
                                    <option value="last_month">Last Month</option>
                                    <option value="this_quarter">This Quarter</option>
                                    <option value="this_year">This Year</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" name="report_type">
                                    <option value="all" selected>All Reports</option>
                                    <option value="sales">Sales Only</option>
                                    <option value="purchases">Purchases Only</option>
                                    <option value="products">Product Performance</option>
                                    <option value="customers">Customer Analysis</option>
                                    <option value="suppliers">Supplier Analysis</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="customDateStart" style="display: none;">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date">
                            </div>
                            <div class="col-md-6" id="customDateEnd" style="display: none;">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                                    <option value="">All Categories</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" name="payment_status">
                                    <option value="">All Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Unpaid">Unpaid</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyFilter()">Apply Filter</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilter()">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function applyFilter() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData);

            // Reload page with filter parameters
            window.location.href = `{{ route('admin.reports') }}?${params.toString()}`;
        }

        function resetFilter() {
            document.getElementById('filterForm').reset();
            window.location.href = `{{ route('admin.reports') }}`;
        }

        // Handle custom date range toggle
        document.querySelector('select[name="date_range"]').addEventListener('change', function() {
            const customFields = document.querySelectorAll('#customDateStart, #customDateEnd');
            if (this.value === 'custom') {
                customFields.forEach(field => field.style.display = 'block');
            } else {
                customFields.forEach(field => field.style.display = 'none');
            }
        });
    </script>

    <style>
        @media print {

            .btn,
            .dropdown,
            .modal,
            .page-header .col-auto {
                display: none !important;
            }

            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
            }

            .page-title {
                font-size: 24px !important;
                margin-bottom: 20px !important;
            }
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .card {
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .progress-sm {
            height: 0.5rem;
        }

        .avatar {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .badge {
            font-size: 0.75rem;
        }

        /* Custom scrollbar for tables */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
@endsection
