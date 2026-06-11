<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.pagination_showing_entries', ['first' => $quotations->firstItem(), 'last' => $quotations->lastItem(), 'total' => $quotations->total()]) }}
    </p>
    <div class="ms-auto">
        {{ $quotations->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
