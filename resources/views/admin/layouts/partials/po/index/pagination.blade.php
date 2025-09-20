<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', ['first' => $pos->firstItem(), 'last' => $pos->lastItem(), 'total' => $pos->total()]) }}
    </p>
    <div class="ms-auto">
        {{ $pos->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
