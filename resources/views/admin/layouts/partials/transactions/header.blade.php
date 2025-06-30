<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    Transaction Management
                </div>
                <h2 class="page-title">
                    <i class="ti ti-history fs-3 me-2 text-primary"></i>
                    All Transactions
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <div class="dropdown">
                        <button class="btn btn-secondary" onclick="exportTransactions()">
                            <i class="ti ti-printer me-1"></i>
                            Export PDF
                        </button>
                        <button class="btn btn-outline-primary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-filter me-1"></i>
                            Filter
                        </button>
                        @include('admin.layouts.partials.transactions.filter-dropdown')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
