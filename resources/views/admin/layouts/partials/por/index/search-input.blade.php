<div class="ms-2 mb-2 text-end">
    {{ __('messages.search_label') }}
    <div class="ms-2">
        <input type="text" id="searchInput" class="form-control form-control-sm" value="{{ request('search', '') }}" autocomplete="off" data-server-search="1">
    </div>
</div>
