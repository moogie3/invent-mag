<div class="card-body border-bottom py-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">{{ __('messages.search_label') }}</label>
            <input type="text" class="form-control" id="searchInput" placeholder="{{ __('messages.search') }}..." value="{{ request('search', '') }}" autocomplete="off" data-server-search="1">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('messages.quotation_status') }}</label>
            <select class="form-select" id="statusFilter" data-onchange="(() => { const u = new URL(window.location.href); this.value ? u.searchParams.set('status', this.value) : u.searchParams.delete('status'); u.searchParams.delete('page'); window.location.href = u.toString(); })();">
                <option value="">All</option>
                @foreach(['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __("messages.quotation_status_{$s}") }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" class="form-control" id="dateFrom" data-onchange="(() => { const u = new URL(window.location.href); this.value ? u.searchParams.set('date_from', this.value) : u.searchParams.delete('date_from'); u.searchParams.delete('page'); window.location.href = u.toString(); })();" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" class="form-control" id="dateTo" data-onchange="(() => { const u = new URL(window.location.href); this.value ? u.searchParams.set('date_to', this.value) : u.searchParams.delete('date_to'); u.searchParams.delete('page'); window.location.href = u.toString(); })();" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('messages.entries') }}</label>
            <select class="form-select" id="entriesSelect" data-onchange="(() => { const u = new URL(window.location.href); this.value ? u.searchParams.set('entries', this.value) : u.searchParams.delete('entries'); u.searchParams.delete('page'); window.location.href = u.toString(); })();">
                <option value="10" {{ request('entries', 10) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('entries') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </div>
</div>
