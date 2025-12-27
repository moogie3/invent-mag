<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', [
            'first' => $wos->firstItem(),
            'last' => $wos->lastItem(),
            'total' => $wos->total(),
        ]) }}
    </p>
    <div class="ms-auto">
        {{ $wos->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
