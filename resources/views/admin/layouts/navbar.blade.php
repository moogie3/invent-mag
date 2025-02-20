<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl d-flex justify-content-between align-items-center">
        <!-- Left Spacer (Balances the Right Icons) -->
        <div class="d-flex" style="width: 150px;"></div>

        <!-- Centered Brand Title -->
        <h1 class="navbar-brand position-absolute start-50 translate-middle-x m-0">
            <a href="{{ route('admin.dashboard') }}" class="nav-link"><i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
        </h1>

        <!-- Right Side Icons -->
        <div class="navbar-nav flex-row order-md-last">
            <div class="d-none d-md-flex">
                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="ti ti-moon fs-2"></i>
                </a>
                <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="ti ti-sun fs-2"></i>
                </a>
                <div class="nav-item dropdown d-none d-md-flex me-3">
                    <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1"
                        aria-label="Show notifications">
                        <i class="ti ti-bell fs-2"></i>
                    </a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                    aria-label="Open user menu">
                    <span class="avatar avatar-sm" style="background-image: url"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div>SANJAYA</div>
                        <div class="mt-1 small text-secondary">admin</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="./profile.html" class="dropdown-item">Profile</a>
                    <a href="{{ route('admin.currency.edit') }}" class="dropdown-item">Settings</a>
                    <a href="./sign-in.html" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>


<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl d-flex justify-content-center">
                <ul class="navbar-nav text-center">
                    <li class="nav-item {{ request()->is('/') || request()->is('admin') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-home fs-2"></i>
                            </span>
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/po', 'admin/po/create', 'admin/po/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.po') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-shopping-cart fs-2"></i>
                            </span>
                            <span class="nav-link-title">Purchase Order</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/sales', 'admin/sales/create', 'admin/sales/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.sales') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-currency-dollar fs-2"></i>
                            </span>
                            <span class="nav-link-title">Sales</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/product', 'admin/product/create', 'admin/product/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.product') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-box fs-2"></i>
                            </span>
                            <span class="nav-link-title">Product</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/supplier', 'admin/supplier/create', 'admin/supplier/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.supplier') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-building fs-2"></i>
                            </span>
                            <span class="nav-link-title">Supplier</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/customer', 'admin/customer/create', 'admin/customer/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.customer') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-user fs-2"></i>
                            </span>
                            <span class="nav-link-title">Customer</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/unit', 'admin/unit/create', 'admin/unit/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.unit') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-universe fs-2"></i>
                            </span>
                            <span class="nav-link-title">Units</span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('admin/category', 'admin/category/create', 'admin/category/edit/*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.category') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                <i class="ti ti-category fs-2"></i>
                            </span>
                            <span class="nav-link-title">Category</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

