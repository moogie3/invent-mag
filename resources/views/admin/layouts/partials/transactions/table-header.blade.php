<div class="card-header">
    <h3 class="card-title">
        <i class="ti ti-list me-2"></i>
        Transaction History
    </h3>
    <div class="card-actions">
        <div class="input-group input-group-md" style="max-width: 300px;">
            <input type="text" class="form-control" placeholder="Search transactions..." id="searchInput"
                value="{{ request('search') }}">
            <button class="btn btn-outline-secondary" type="button" onclick="searchTransactions()">
                <i class="ti ti-search"></i>
            </button>
        </div>
    </div>
</div>
