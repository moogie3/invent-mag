<div class="pt-2 mt-auto d-flex justify-content-between align-items-center">
    <a href="{{ $metric['route'] }}" class="text-secondary text-decoration-none">
        {{ __('messages.view_details') }} <i class="ti ti-arrow-right ms-1"></i>
    </a>
    @include('admin.layouts.partials.dashboard.trend-badge', ['metric' => $metric])
</div>
