<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><i class="ti ti-bell me-2"></i>{{ __('messages.notification') }}</h2>
                @if(isset($hasNotifications) && $hasNotifications)
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-medium" id="clear-all-btn">
                        <i class="ti ti-checks me-2"></i> Clear All Notifications
                    </button>
                @endif
            </div>
            <hr class="my-0">
            <div class="row g-0">
                <div class="col-12 col-md-3 border-end">
                    @include('admin.layouts.menu')
                </div>
                <div class="col-12 col-md-9">
                    <div class="card-body">
                        @include('admin.layouts.partials.notification.tabs')
                        @include('admin.layouts.partials.notification.content')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
