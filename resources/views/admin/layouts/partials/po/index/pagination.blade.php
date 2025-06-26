<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        Showing {{ $pos->firstItem() }} to {{ $pos->lastItem() }} of {{ $pos->total() }} entries
    </p>
    <div class="ms-auto">
        {{ $pos->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
