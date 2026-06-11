<div class="card-body border-bottom py-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">{{ __('messages.search_label') }}</label>
            <input type="text" class="form-control" id="searchInput" placeholder="{{ __('messages.search') }}..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('messages.quotation_status') }}</label>
            <select class="form-select" id="statusFilter">
                <option value="">All</option>
                @foreach(['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __("messages.quotation_status_{$s}") }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" class="form-control" id="dateFrom" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" class="form-control" id="dateTo" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('messages.entries') }}</label>
            <select class="form-select" id="entriesSelect">
                <option value="10" {{ request('entries', 10) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('entries') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </div>
</div>
