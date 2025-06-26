<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        Showing {{ $sales->firstItem() }} to {{ $sales->lastItem() }} of {{ $sales->total() }} entries
    </p>
    <div class="ms-auto">
        {{ $sales->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
