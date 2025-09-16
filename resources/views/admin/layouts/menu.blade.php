<div class="card-body">
    <h3 class="header"><i class="ti ti-menu-2 me-2"></i>Setting List</h3>
    <hr class="my-3">
    <div class="list-group list-group-transparent">
    <a href="{{ route('admin.setting.index') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.index') ? 'active' : '' }}">
        <i class="ti ti-settings-2 fs-2 me-2"></i> {{ __('messages.system') }}
    </a>
    <a href="{{ route('admin.setting.profile.edit') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.profile.edit') ? 'active' : '' }}">
        <i class="ti ti-user-circle fs-2 me-2"></i> {{ __('messages.profile') }}
    </a>
    <a href="{{ route('admin.setting.currency.edit') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.currency.edit') ? 'active' : '' }}">
        <i class="ti ti-coin fs-2 me-2"></i> {{ __('messages.currency') }}
    </a>
    <a href="{{ route('admin.setting.tax') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.tax') ? 'active' : '' }}">
        <i class="ti ti-receipt-tax fs-2 me-2"></i> {{ __('messages.tax') }}
    </a>
    <a href="{{ route('admin.setting.category') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.category') ? 'active' : '' }}">
        <i class="ti ti-tags fs-2 me-2"></i> {{ __('messages.categories') }}
    </a>
    <a href="{{ route('admin.setting.unit') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.unit') ? 'active' : '' }}">
        <i class="ti ti-ruler-2 fs-2 me-2"></i> {{ __('messages.units') }}
    </a>
</div>

</div>
