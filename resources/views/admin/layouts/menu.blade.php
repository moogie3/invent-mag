<div class="card-body">
    <h4 class="subheader">Business settings</h4>
    <div class="list-group list-group-transparent">
        <a href="{{ route('admin.setting.notifications') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.notifications') ? 'active' : '' }}">My
            Notifications</a>

        <a href="{{ route('admin.setting.profile.edit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.profile.edit') ? 'active' : '' }}">Account
            Settings</a>

        <a href="{{ route('admin.setting.currency.edit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.currency.edit') ? 'active' : '' }}">Currency
            Settings</a>

        <a href="{{ route('admin.setting.unit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.unit') ? 'active' : '' }}">Units
            Settings</a>

        <a href="{{ route('admin.setting.category') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.category') ? 'active' : '' }}">Category
            Settings</a>

        <a href="{{ route('admin.setting.tax') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.tax') ? 'active' : '' }}">Tax
            Settings</a>

        @can('view-users')
            <a href="{{ route('admin.users.index') }}"
                class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') || request()->routeIs('admin.users.edit') ? 'active' : '' }}">User
                Management</a>
        @endcan

    </div>
</div>
