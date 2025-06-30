<div class="ms-auto text-secondary no-print">
    Search :
    <div class="ms-2 d-inline-block">
        <input type="text" id="searchInput" class="form-control form-control-sm">
    </div>
    <div class="text-end">
        Show
        <div class="mx-1 mt-2 d-inline-block">
            <select name="entries" id="entriesSelect" onchange="window.location.href='?entries=' + this.value;">
                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
            </select> entries
        </div>
    </div>
</div>
