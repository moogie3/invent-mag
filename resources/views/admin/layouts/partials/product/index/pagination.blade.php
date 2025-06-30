<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} entries
    </p>
    <div class="ms-auto">
        {{ $products->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
