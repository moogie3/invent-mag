<div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 300px;">
    <form id="filterForm" method="GET">
        <div class="mb-3">
            <label class="form-label">Transaction Type</label>
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>
                    Sales</option>
                <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Purchases</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Status</label>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Paid" {{ request('status') == 'Paid' ? 'selected' : '' }}>
                    Paid</option>
                <option value="Partial" {{ request('status') == 'Partial' ? 'selected' : '' }}>Partial</option>
                <option value="Unpaid" {{ request('status') == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Date Range</label>
            <select name="date_range" class="form-select">
                <option value="all" {{ request('date_range') == 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                <option value="this_week" {{ request('date_range') == 'this_week' ? 'selected' : '' }}>This Week
                </option>
                <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month
                </option>
                <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month
                </option>
                <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Range
                </option>
            </select>
        </div>
        <div id="customDateRange" class="mb-3"
            style="display: {{ request('date_range') == 'custom' ? 'block' : 'none' }};">
            <div class="row">
                <div class="col">
                    <label class="form-label">From</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col">
                    <label class="form-label">To</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="ti ti-search me-1"></i>
                Apply
            </button>
            <a href="{{ route('admin.transactions') }}" class="btn btn-outline-secondary">
                <i class="ti ti-x me-1"></i>
                Clear
            </a>
        </div>
    </form>
</div>
