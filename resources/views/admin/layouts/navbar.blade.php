<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl d-flex justify-content-between align-items-center">
        <!-- Left Spacer -->
        <div class="d-flex" style="width: 150px;"></div>

        <!-- Centered Brand Title -->
        <h1 class="navbar-brand text-center mx-auto m-0">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">
                <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
            </a>
        </h1>

        <!-- Right Side Icons (Always Visible) -->
        <div class="d-flex align-items-center">
            <!-- Dark Mode Toggle -->
            <a href="?theme=dark" class="nav-link px-2 hide-theme-dark" title="Enable dark mode" data-bs-toggle="tooltip"
                data-bs-placement="bottom">
                <i class="ti ti-moon fs-2"></i>
            </a>
            <a href="?theme=light" class="nav-link px-2 hide-theme-light" title="Enable light mode"
                data-bs-toggle="tooltip" data-bs-placement="bottom">
                <i class="ti ti-sun fs-2"></i>
            </a>

            <!-- Notification -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link px-2" data-bs-toggle="dropdown" tabindex="-1"
                    aria-label="Show notifications">
                    <i class="ti ti-bell fs-2"></i>
                </a>
            </div>

            <!-- User Dropdown -->
            <div class="nav-item dropdown ms-2">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                    aria-label="Open user menu">
                    <span class="avatar avatar-sm" style="background-image: url"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div>SANJAYA</div>
                        <div class="mt-1 small text-secondary">admin</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">Profile Setting</a>
                    <a href="{{ route('admin.currency.edit') }}" class="dropdown-item">Currency Setting</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    <a href="#" class="dropdown-item"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<header class="navbar navbar-expand-md">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="navbar-nav mx-auto text-center">
                <li class="nav-item {{ request()->is('/') || request()->is('admin') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.dashboard') }}">
                        <i class="ti ti-home fs-2"></i>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->is('/') || request()->is('admin') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.ds') }}">
                        <i class="ti ti-presentation-analytics fs-2"></i>
                        <span class="nav-link-title">Daily Sales</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/sales', 'admin/sales/create', 'admin/sales/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.sales') }}">
                        <i class="ti ti-currency-dollar fs-2"></i>
                        <span class="nav-link-title">Sales</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/po', 'admin/po/create', 'admin/po/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.po') }}">
                        <i class="ti ti-shopping-cart fs-2"></i>
                        <span class="nav-link-title">Purchase Order</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/product', 'admin/product/create', 'admin/product/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.product') }}">
                        <i class="ti ti-box fs-2"></i>
                        <span class="nav-link-title">Product</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/customer', 'admin/customer/create', 'admin/customer/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.customer') }}">
                        <i class="ti ti-user fs-2"></i>
                        <span class="nav-link-title">Customer</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/supplier', 'admin/supplier/create', 'admin/supplier/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.supplier') }}">
                        <i class="ti ti-building fs-2"></i>
                        <span class="nav-link-title">Supplier</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/unit', 'admin/unit/create', 'admin/unit/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.unit') }}">
                        <i class="ti ti-universe fs-2"></i>
                        <span class="nav-link-title">Units</span>
                    </a>
                </li>
                <li
                    class="nav-item {{ request()->is('admin/category', 'admin/category/create', 'admin/category/edit/*') ? 'active' : '' }}">
                    <a class="nav-link gap-1" href="{{ route('admin.category') }}">
                        <i class="ti ti-category fs-2"></i>
                        <span class="nav-link-title">Category</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
