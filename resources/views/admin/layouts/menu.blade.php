<div class="card-body">
    <h3 class="header"><i class="ti ti-menu-2 me-2"></i>Setting List</h3>
    <hr class="my-3">
    <div class="list-group list-group-transparent">
        <a href="{{ route('admin.setting.notifications') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.notifications') ? 'active' : '' }}">
            <i class="ti ti-bell fs-2 me-2"></i> {{ __('messages.my_notifications') }}
        </a>
        <a href="{{ route('admin.setting.profile.edit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.profile.edit') ? 'active' : '' }}">
            <i class="ti ti-user-circle fs-2 me-2"></i> {{ __('messages.profile') }}
        </a>
        <a href="{{ route('admin.setting.currency.edit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.currency.edit') ? 'active' : '' }}">
            <i class="ti ti-coin fs-2 me-2"></i> {{ __('messages.currency') }}
        </a>
        <a href="{{ route('admin.setting.unit') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.unit') ? 'active' : '' }}">
            <i class="ti ti-ruler-2 fs-2 me-2"></i> {{ __('messages.units') }}
        </a>
        <a href="{{ route('admin.setting.category') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.category') ? 'active' : '' }}">
            <i class="ti ti-tags fs-2 me-2"></i> {{ __('messages.categories') }}
        </a>
        <a href="{{ route('admin.setting.tax') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.tax') ? 'active' : '' }}">
            <i class="ti ti-receipt-tax fs-2 me-2"></i> {{ __('messages.tax') }}
        </a>
        <a href="{{ route('admin.setting.accounting') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.accounting') ? 'active' : '' }}">
            <i class="ti ti-calculator fs-2 me-2"></i> {{ __('messages.accounting') }}
        </a>
        <a href="{{ route('admin.setting.index') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.index') ? 'active' : '' }}">
            <i class="ti ti-settings-2 fs-2 me-2"></i> {{ __('messages.system') }}
        </a>
        <a href="{{ route('admin.setting.plan') }}"
            class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.plan') || request()->routeIs('admin.setting.plan.*') ? 'active' : '' }}">
            <i class="ti ti-crown fs-2 me-2"></i> {{ __('plan.title') }}
        </a>
        @role('superuser')
            <a href="{{ route('admin.users.index') }}"
                class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') || request()->routeIs('admin.users.edit') ? 'active' : '' }}">
                <i class="ti ti-users fs-2 me-2"></i> {{ __('messages.user_management') }}
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="collapse" data-bs-target="#adminCollapse" role="button" aria-expanded="{{ request()->routeIs('admin.hr.*') ? 'true' : 'false' }}">
                <i class="ti ti-shield-cog fs-2 me-2"></i> {{ __('messages.administration') }}
                <i class="ti ti-chevron-down ms-auto"></i>
            </a>
            <div class="collapse {{ request()->routeIs('admin.hr.*') ? 'show' : '' }}" id="adminCollapse">
                <a href="{{ route('admin.hr.employee.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center ps-5 {{ request()->routeIs('admin.hr.employee.*') ? 'active' : '' }}">
                    <i class="ti ti-id-badge fs-2 me-2"></i> {{ __('messages.hr_employees') }}
                </a>
                <a href="{{ route('admin.hr.attendance.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center ps-5 {{ request()->routeIs('admin.hr.attendance.*') ? 'active' : '' }}">
                    <i class="ti ti-calendar-check fs-2 me-2"></i> {{ __('messages.hr_attendance') }}
                </a>
                <a href="{{ route('admin.hr.payroll.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center ps-5 {{ request()->routeIs('admin.hr.payroll.*') ? 'active' : '' }}">
                    <i class="ti ti-wallet fs-2 me-2"></i> {{ __('messages.hr_payroll') }}
                </a>
            </div>
            <a href="{{ route('admin.setting.backup.index') }}"
                class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('admin.setting.backup.index') ? 'active' : '' }}">
                <i class="ti ti-database fs-2 me-2"></i> {{ __('messages.backup') }}
            </a>
        @endrole
        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center trigger-tutorial-modal-general">
            <i class="ti ti-help fs-2 me-2"></i> {{ __('messages.tutorial') }}
        </a>
    </div>
</div>