<div data-pagination-footer class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', ['first' => $sales->firstItem() ?? 0, 'last' => $sales->lastItem() ?? 0, 'total' => $sales->total()]) }}
    </p>
    <div class="ms-auto">
        {{ $sales->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
