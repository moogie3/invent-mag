<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">
        {{ __('messages.showing') }} {{ $sales->firstItem() }} {{ __('messages.to') }} {{ $sales->lastItem() }} {{ __('messages.of') }} {{ $sales->total() }} {{ __('messages.entries') }}
    </p>
    <div class="ms-auto">
        {{ $sales->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
</div>
