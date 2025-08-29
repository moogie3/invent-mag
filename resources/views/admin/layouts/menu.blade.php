<div class="card-body">
    <h3 class="header"><i class="ti ti-settings me-2"></i>SETTINGS</h3>
    <hr class="my-3">
    <div class="list-group list-group-transparent">
        <a href="{{ route('admin.setting.notifications') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.notifications') ? 'active-menu-item' : '' }}">
            <i class="ti ti-bell me-2"></i>My Notifications
        </a>
        <a href="{{ route('admin.setting.profile.edit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.profile.edit') ? 'active-menu-item' : '' }}">
            <i class="ti ti-user-cog me-2"></i>Account Settings
        </a>
        <a href="{{ route('admin.setting.currency.edit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.currency.edit') ? 'active-menu-item' : '' }}">
            <i class="ti ti-coin me-2"></i>Currency Settings
        </a>
        <a href="{{ route('admin.setting.unit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.unit') ? 'active-menu-item' : '' }}">
            <i class="ti ti-ruler me-2"></i>Units Settings
        </a>
        <a href="{{ route('admin.setting.category') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.category') ? 'active-menu-item' : '' }}">
            <i class="ti ti-tag me-2"></i>Category Settings
        </a>
        <a href="{{ route('admin.setting.tax') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.tax') ? 'active-menu-item' : '' }}">
            <i class="ti ti-receipt-tax me-2"></i>Tax Settings
        </a>
        @can('view-users')
            <a href="{{ route('admin.users.index') }}"
                class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') || request()->routeIs('admin.users.edit') ? 'active-menu-item' : '' }}">
                <i class="ti ti-users me-2"></i>User Management
            </a>
        @endcan

    </div>
</div>
