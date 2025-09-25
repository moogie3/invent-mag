<header class="navbar navbar-expand-md d-print-none nav-container">
    <div class="container-xl d-flex align-items-center justify-content-between position-relative">
        <div class="d-flex flex-grow-1 nav-left align-items-center">
            <!-- Sidebar toggle will be in the sidebar itself -->
        </div>

        <!-- Centered Brand Title -->
        <h1 class="navbar-brand position-absolute top-50 start-50 translate-middle text-center" id="navbar-brand-title">
            <a class="nav-link" id="brand-trigger">
                <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
            </a>
        </h1>

        <!-- Navigation Dropdown -->
        <nav class="nav-dropdown d-none d-md-flex" id="nav-dropdown">
            <ul class="d-flex gap-3">
                @foreach ($navigationItems as $item)
                    {{-- Check if the item is 'Reports' to display it unfiltered --}}
                    @if (isset($item['title']) && $item['title'] === 'Reports')
                        <li class="nav-item dropdown" id="reports-nav-item">
                            <a class="nav-link dropdown-toggle" href="#navbar-{{ Str::slug($item['title']) }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <i class="{{ $item['icon'] }}"></i>
                                <span class="nav-link-title">
                                    {{ __($item['title']) }}
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        {{-- Loop through children without permission check --}}
                                        @foreach ($item['children'] as $child)
                                            <a class="dropdown-item" href="{{ route($child['route']) }}">
                                                <i class="{{ $child['icon'] ?? 'ti ti-point' }}"></i>
                                                <span>{{ __($child['title']) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </li>
                    @else
                        {{-- For all other items, apply the original permission check --}}
                        @can($item['permission'] ?? null)
                            @if (isset($item['children']))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#navbar-{{ Str::slug($item['title']) }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                        <i class="{{ $item['icon'] }}"></i>
                                        <span class="nav-link-title">
                                            {{ __($item['title']) }}
                                        </span>
                                    </a>
                                    <div class="dropdown-menu">
                                        <div class="dropdown-menu-columns">
                                            <div class="dropdown-menu-column">
                                                @foreach ($item['children'] as $child)
                                                    @can($child['permission'] ?? null)
                                                        <a class="dropdown-item" href="{{ route($child['route']) }}">
                                                            {{ __($child['title']) }}
                                                        </a>
                                                    @endcan
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @else
                                <li><a href="{{ route($item['route']) }}"><i
                                            class="{{ $item['icon'] }}"></i><span class="nav-link-title">{{ __($item['title']) }}</span></a></li>
                            @endif
                        @endcan
                    @endif
                @endforeach
            </ul>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div class="nav-overlay" id="nav-overlay"></div>



        <!-- Right Side Icons -->
        <div class="d-flex flex-grow-1 justify-content-end align-items-center" id="navbar-right-content">


            <!-- Theme Toggle Button -->
            <div class="nav-item me-2" id="theme-toggle-navbar-container">
                <a href="#" class="nav-link px-0" id="theme-toggle-navbar">
                    <i class="ti ti-sun fs-2 theme-icon-light"></i>
                    <i class="ti ti-moon fs-2 theme-icon-dark" style="display: none;"></i>
                </a>
            </div>

            <!-- Modified Notification Bell Section -->
            <div class="nav-item dropdown me-3">
                <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown"
                    aria-expanded="false" id="notification-bell">
                    <i class="ti ti-bell fs-2"></i>

                    @if (isset($notificationCount) && $notificationCount > 0)
                        <span id="notification-dot"
                            class="position-absolute bg-danger border border-light rounded-circle">
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0"
                    style="width: 380px; max-height: 500px;">

                    <!-- Notification Header -->
                    <div class="border-bottom d-flex justify-content-between align-items-center p-3">
                        <h3 class="mb-0 fw-bold">Notifications</h3>
                        @if (isset($notificationCount) && $notificationCount > 0)
                            <span class="badge bg-primary-lt rounded-pill">{{ $notificationCount }}</span>
                        @endif
                    </div>

                    <!-- Notification Tabs -->
                    <div class="notification-tabs">
                        <ul class="nav nav-tabs nav-fill border-bottom" id="notificationTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-2" id="financial-tab" data-bs-toggle="tab"
                                    data-bs-target="#financial" type="button" role="tab">
                                    <i class="ti ti-receipt me-1"></i>PO & Sales
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-2" id="inventory-tab" data-bs-toggle="tab"
                                    data-bs-target="#inventory" type="button" role="tab">
                                    <i class="ti ti-package me-1"></i>Inventory
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-2" id="system-tab" data-bs-toggle="tab"
                                    data-bs-target="#system" type="button" role="tab">
                                    <i class="ti ti-adjustments me-1"></i>System
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-0" style="max-height: 350px; overflow-y: auto;">
                            <!-- Financial Tab Content -->
                            <div class="tab-pane fade show active" id="financial" role="tabpanel">
                                <div class="notification-group py-2">
                                    <div class="small fw-medium text-muted px-3 py-1">Purchase Orders</div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'purchase';
                                                })->count() > 0)
                                        @php
                                            $purchaseNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'purchase';
                                            });
                                            $purchaseCount = $purchaseNotifications->count();
                                        @endphp

                                        @foreach ($purchaseNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 border-bottom notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="avatar avatar-sm bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($purchaseCount > 3)
                                            <div class="text-center py-2 text-muted small">
                                                + {{ $purchaseCount - 3 }} more purchase notifications
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-center py-3 text-muted">No purchase notifications</div>
                                    @endif

                                    <div class="small fw-medium text-muted px-3 py-1 mt-1">Sales</div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'sales';
                                                })->count() > 0)
                                        @php
                                            $salesNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'sales';
                                            });
                                            $salesCount = $salesNotifications->count();
                                        @endphp

                                        @foreach ($salesNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 border-bottom notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="avatar avatar-sm bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($salesCount > 3)
                                            <div class="text-center py-2 text-muted small">
                                                + {{ $salesCount - 3 }} more sales notifications
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-center py-3 text-muted">No sales notifications</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Inventory Tab Content -->
                            <div class="tab-pane fade" id="inventory" role="tabpanel">
                                <div class="notification-group py-2">
                                    <div class="small fw-medium text-muted px-3 py-1">Low Stock Alerts</div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'product' && $n['status'] == 'Low Stock';
                                                })->count() > 0)
                                        @php
                                            $lowStockNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'product' && $n['status'] == 'Low Stock';
                                            });
                                            $lowStockCount = $lowStockNotifications->count();
                                        @endphp

                                        @foreach ($lowStockNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 border-bottom notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="avatar avatar-sm bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($lowStockCount > 3)
                                            <div class="text-center py-2 text-muted small">
                                                + {{ $lowStockCount - 3 }} more low stock alerts
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-center py-3 text-muted">No low stock alerts</div>
                                    @endif

                                    <div class="small fw-medium text-muted px-3 py-1 mt-1">Expiring Products</div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'product' && $n['status'] == 'Expiring Soon';
                                                })->count() > 0)
                                        @php
                                            $expiringNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'product' && $n['status'] == 'Expiring Soon';
                                            });
                                            $expiringCount = $expiringNotifications->count();
                                        @endphp

                                        @foreach ($expiringNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 border-bottom notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="avatar avatar-sm bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($expiringCount > 3)
                                            <div class="text-center py-2 text-muted small">
                                                + {{ $expiringCount - 3 }} more expiring product alerts
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-center py-3 text-muted">No expiring products</div>
                                    @endif
                                </div>
                            </div>

                            <!-- System Tab Content -->
                            <div class="tab-pane fade" id="system" role="tabpanel">
                                <div class="notification-group py-2">
                                    <div class="small fw-medium text-muted px-3 py-1">System Updates</div>

                                    <div class="text-center py-3 text-muted">No system notifications</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer with View All Link -->
                    <div class="border-top p-2 text-center">
                        <a href="{{ route('admin.notifications') }}" class="text-primary fw-medium">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>

            <div class="nav-item dropdown ms-2" id="avatar-dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                    @if (Auth::check())
                        <span class="avatar avatar-sm"
                            style="background-image: url('{{ Auth::user()->avatar && Storage::disk('public')->exists(Auth::user()->avatar) ? asset('storage/' . Auth::user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=random' }}');"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="mt-1 small text-secondary">{{ Auth::user()->getRoleNames()->first() }}</div>
                        </div>
                    @else
                        <span class="avatar avatar-sm"
                            style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ __('messages.guest') }}</div>
                            <div class="mt-1 small text-secondary">{{ __('messages.not_logged_in') }}</div>
                        </div>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('admin.setting.profile.edit') }}" class="dropdown-item">
                        <i class="ti ti-settings me-2"></i>{{ __('messages.settings') }}
                    </a>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    <a href="#" class="dropdown-item"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ti ti-logout me-2"></i>{{ __('messages.logout') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

@vite('resources/css/navbar.css')
@vite('resources/js/admin/layouts/navbar.js')
