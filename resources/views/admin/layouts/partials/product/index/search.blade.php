<div class="ms-auto text-secondary no-print">
    {{ __('messages.search_label') }}
    <div class="ms-2 d-inline-block">
        <input type="text" id="searchInput" class="form-control form-control-sm"
            value="{{ request('search', '') }}"
            placeholder="{{ __('messages.search') }}…"
            data-server-search="product" autocomplete="off">
    </div>
    <div class="text-end">
        Show
        <div class="mx-1 mt-2 d-inline-block">
            <select name="entries" id="entriesSelect" data-onchange="(() => { const u = new URL(window.location.href); u.searchParams.set('entries', this.value); u.searchParams.delete('page'); window.location.href = u.toString(); })();">
                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
            </select> entries
        </div>
    </div>
</div>
