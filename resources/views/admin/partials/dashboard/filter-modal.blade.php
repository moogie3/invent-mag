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
