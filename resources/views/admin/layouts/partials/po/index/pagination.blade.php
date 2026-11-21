<div data-pagination-footer class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', ['first' => $pos->firstItem() ?? 0, 'last' => $pos->lastItem() ?? 0, 'total' => $pos->total()]) }}
    </p>
    <div class="ms-auto">
        {{ $pos->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
