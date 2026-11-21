<div id="productPaginationFooter" data-pagination-footer class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', ['first' => $products->firstItem() ?? 0, 'last' => $products->lastItem() ?? 0, 'total' => $products->total()]) }}
    </p>
    <div class="ms-auto">
        {{ $products->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
