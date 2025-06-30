<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        Showing {{ $wos->firstItem() }} to {{ $wos->lastItem() }} of {{ $wos->total() }} entries
    </p>
    <div class="ms-auto">
        {{ $wos->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
