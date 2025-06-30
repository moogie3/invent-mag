@if (method_exists($transactions, 'links') && $transactions->hasPages())
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">
            Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of
            {{ $transactions->total() }} entries
        </p>
        <div class="ms-auto">
            {{ $transactions->appends(request()->query())->links('vendor.pagination.tabler') }}
        </div>
    </div>
@endif
