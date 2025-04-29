<header class="navbar navbar-expand-md d-print-none nav-container">
    <div class="container-xl d-flex justify-content-between align-items-center">
        <div class="d-flex flex-shrink-0 nav-left">
            <!-- Hamburger Button for Mobile -->
            <button class="navbar-toggler d-md-none" type="button" id="mobile-menu-toggle">
                <i class="ti ti-menu fs-2"></i>
            </button>
        </div>

        <!-- Centered Brand Title -->
        <h1 class="navbar-brand text-center mx-auto position-relative">
            <a class="nav-link" id="brand-trigger">
                <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
            </a>
        </h1>

        <!-- Navigation Dropdown -->
        <nav class="nav-dropdown d-none d-md-flex" id="nav-dropdown">
            <ul class="d-flex gap-3">
                <li><a href="{{ route('admin.dashboard') }}"><i class="ti ti-home"></i>Dashboard</a></li>
                <li><a href="{{ route('admin.pos') }}"><i class="ti ti-cash"></i>POS</a></li>
                <li><a href="{{ route('admin.sales') }}"><i class="ti ti-report-money"></i>Sales</a></li>
                <li><a href="{{ route('admin.po') }}"><i class="ti ti-shopping-cart"></i>Purchase Order</a></li>
                <li><a href="{{ route('admin.product') }}"><i class="ti ti-package"></i>Product</a></li>
                <li><a href="{{ route('admin.customer') }}"><i class="ti ti-users"></i>Customer</a></li>
                <li><a href="{{ route('admin.supplier') }}"><i class="ti ti-truck"></i>Supplier</a></li>
                <li><a href="{{ route('admin.warehouse') }}"><i class="ti ti-building-warehouse"></i>Warehouse</a></li>
            </ul>
        </nav>

        <div class="mobile-nav d-md-none" id="mobile-nav">
            <ul>
                <li><a href="{{ route('admin.dashboard') }}"><i class="ti ti-home"></i>Dashboard</a></li>
                <li><a href="{{ route('admin.pos') }}"><i class="ti ti-cash"></i>POS</a></li>
                <li><a href="{{ route('admin.sales') }}"><i class="ti ti-report-money"></i></i>Sales</a></li>
                <li><a href="{{ route('admin.po') }}"><i class="ti ti-shopping-cart"></i>Purchase Order</a></li>
                <li><a href="{{ route('admin.product') }}"><i class="ti ti-package"></i>Product</a></li>
                <li><a href="{{ route('admin.customer') }}"><i class="ti ti-users"></i>Customer</a></li>
                <li><a href="{{ route('admin.supplier') }}"><i class="ti ti-truck"></i>Supplier</a></li>
                <li><a href="{{ route('admin.warehouse') }}"><i class="ti ti-building-warehouse"></i>Warehouse</a></li>
                <li><a href="?theme=light" class="hide-theme-light">
                        <i class="ti ti-sun"></i>Light Mode
                    </a><a href="?theme=dark" class="hide-theme-dark">
                        <i class="ti ti-moon"></i>Dark Mode
                    </a></li>
                <li><a href="{{ route('admin.setting.notifications') }}" class="dropdown-item"><i
                            class="ti ti-settings"></i>Settings</a></li>
                <li><a href="#" class="dropdown-item"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                            class="ti ti-logout-2"></i>
                        Logout
                    </a></li>
            </ul>
        </div>

        <!-- Right Side Icons -->
        <div class="d-none d-md-flex d-flex align-items-center">
            <a href="?theme=dark" class="nav-link px-2 hide-theme-dark">
                <i class="ti ti-moon fs-2"></i>
            </a>
            <a href="?theme=light" class="nav-link px-2 hide-theme-light">
                <i class="ti ti-sun fs-2"></i>
            </a>

            <!-- Single Notification Bell -->
            <div class="nav-item dropdown me-3">
                <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="ti ti-bell fs-2"></i>

                    @if (isset($notificationCount) && $notificationCount > 0)
                        <span id="notification-dot"
                            class="position-absolute bg-danger border border-light rounded-circle">
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <h6 class="dropdown-header">Notifications</h6>

                    @if (isset($notifications) && $notifications->count() > 0)
                        @foreach ($notifications as $notification)
                            <a href="{{ route('admin.notifications.view', $notification['id']) }}"
                                class="dropdown-item d-flex align-items-center notification-item"
                                data-notification-id="{{ $notification['id'] }}">
                                <span
                                    class="badge bg-{{ $notification['urgency'] == 'high' ? 'danger' : ($notification['urgency'] == 'medium' ? 'warning' : 'info') }} me-2"></span>
                                <div>
                                    <strong>{{ $notification['title'] }}</strong>
                                    <div class="text-muted small">{{ $notification['description'] }}</div>
                                </div>
                            </a>
                        @endforeach
                    @else
                        <div class="dropdown-item text-muted text-center">No new notifications</div>
                    @endif

                    @if (isset($notifications) && $notifications->count() > 0)
                        <div class="dropdown-divider mb-0 mt-0"></div>
                        <a href="{{ route('admin.setting.notifications') }}" class="dropdown-item text-center">
                            View all notifications
                        </a>
                    @endif
                </div>
            </div>

            <div class="nav-item dropdown ms-2">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                    @if (Auth::check())
                        <span class="avatar avatar-sm"
                            style="background-image: url('{{ asset('storage/' . Auth::user()->avatar) }}');"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ Auth::user()->shopname ?? 'No Shop Name' }}</div>
                            <div class="mt-1 small text-secondary">{{ Auth::user()->role }}</div>
                        </div>
                    @else
                        <span class="avatar avatar-sm"
                            style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>Guest</div>
                            <div class="mt-1 small text-secondary">Not logged in</div>
                        </div>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('admin.setting.profile.edit') }}" class="dropdown-item">Settings</a>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
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

<link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
<script src="{{ asset('js/layouts/navbar.js') }}" defer></script>
