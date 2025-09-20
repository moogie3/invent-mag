<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', ['firstItem' => $products->firstItem(), 'lastItem' => $products->lastItem(), 'total' => $products->total()]) }}
    </p>
    <div class="ms-auto">
        {{ $products->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
