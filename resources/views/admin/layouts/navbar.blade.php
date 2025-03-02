<style>
    /* Parent container */
    .nav-container {
        position: relative;
    }

    /* Dark overlay */
    .nav-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out;
        z-index: 998;
    }

    /* Navigation dropdown */
    .nav-dropdown {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        width: max-content;
        background: rgba(20, 20, 20, 0.95);
        box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.2);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out;
        z-index: 999;
        border-radius: 8px;
        padding: 10px 20px;
    }

    /* Show menu when active */
    .nav-container.active .nav-overlay {
        opacity: 1;
        visibility: visible;
    }

    .nav-container.active .nav-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    /* Horizontal menu */
    .nav-dropdown ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .nav-dropdown li {
        text-align: center;
    }

    .nav-dropdown a {
        color: white;
        text-decoration: none;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        white-space: nowrap;
    }

    .nav-dropdown a:hover {
        text-decoration: underline;
    }

    /* Ensure icons are visible */
    .nav-dropdown a i {
        color: white;
        font-size: 18px;
    }

    /* Make sure 'Invent-MAG' remains white */
    .navbar-brand a {
        text-decoration: none;
    }
</style>

<header class="navbar navbar-expand-md d-print-none nav-container">
    <div class="container-xl d-flex justify-content-between align-items-center">
        <!-- Left Spacer -->
        <div class="d-flex" style="width: 150px;"></div>

        <!-- Centered Brand Title -->
        <h1 class="navbar-brand text-center mx-auto m-0 position-relative">
            <a href="{{ route('admin.dashboard') }}" class="nav-link" id="brand-trigger">
                <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
            </a>
        </h1>

        <!-- Overlay -->
        <div class="nav-overlay" id="nav-overlay"></div>

        <!-- Navigation Dropdown -->
        <div class="nav-dropdown" id="nav-dropdown">
            <ul>
                <li><a href="{{ route('admin.dashboard') }}"><i class="ti ti-home"></i>Dashboard</a></li>
                <li><a href="{{ route('admin.ds') }}"><i class="ti ti-calendar"></i>Daily Sales</a></li>
                <li><a href="{{ route('admin.sales') }}"><i class="ti ti-shopping-cart"></i>Sales</a></li>
                <li><a href="{{ route('admin.po') }}"><i class="ti ti-file-invoice"></i>Purchase Order</a></li>
                <li><a href="{{ route('admin.product') }}"><i class="ti ti-package"></i>Product</a></li>
                <li><a href="{{ route('admin.customer') }}"><i class="ti ti-users"></i>Customer</a></li>
                <li><a href="{{ route('admin.supplier') }}"><i class="ti ti-truck"></i>Supplier</a></li>
                <li><a href="{{ route('admin.unit') }}"><i class="ti ti-ruler"></i>Units</a></li>
                <li><a href="{{ route('admin.category') }}"><i class="ti ti-tag"></i>Category</a></li>
            </ul>
        </div>

        <!-- Right Side Icons -->
        <div class="d-flex align-items-center">
            <a href="?theme=dark" class="nav-link px-2 hide-theme-dark">
                <i class="ti ti-moon fs-2"></i>
            </a>
            <a href="?theme=light" class="nav-link px-2 hide-theme-light">
                <i class="ti ti-sun fs-2"></i>
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="ti ti-bell fs-2"></i>
                    @php
                        $hasDueNotes = false;
                        foreach ($purchaseOrders as $po) {
                            $today = now();
                            $dueDate = $po->due_date;
                            $diffDays = $today->diffInDays($dueDate, false);
                            if ($diffDays <= 7 && $po->status !== 'Paid') {
                                $hasDueNotes = true;
                                break;
                            }
                        }
                    @endphp
                    @if ($hasDueNotes)
                        <span
                            class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <h6 class="dropdown-header">Notifications</h6>
                    @php
                        $hasNotifications = false;
                    @endphp
                    @foreach ($purchaseOrders as $po)
                        @php
                            $dueDate = $po->due_date;
                            $diffDays = $today->diffInDays($dueDate, false);
                        @endphp
                        @if ($diffDays <= 7 && $po->status !== 'Paid')
                            <a href="{{ route('admin.po') }}" class="dropdown-item d-flex align-items-center">
                                <span class="badge bg-danger me-2"></span>
                                Due Note: PO #{{ $po->id }} - {{ $po->due_date->format('M d, Y') }}
                            </a>
                            @php $hasNotifications = true; @endphp
                        @endif
                    @endforeach
                    @if (!$hasNotifications)
                        <div class="dropdown-item text-muted text-center">No new notifications</div>
                    @endif
                </div>
            </div>
            <div class="nav-item dropdown ms-2">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ asset('storage/' . Auth::user()->avatar) }}');"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ Auth::user()->shopname ?? 'No Shop Name' }}</div>
                        <div class="mt-1 small text-secondary">{{ Auth::user()->role }}</div>
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const brandTrigger = document.getElementById("brand-trigger");
        const navContainer = document.querySelector(".nav-container");
        const overlay = document.getElementById("nav-overlay");
        const dropdown = document.getElementById("nav-dropdown");

        let timeout;

        // Show dropdown on hover
        brandTrigger.addEventListener("mouseenter", function() {
            clearTimeout(timeout);
            navContainer.classList.add("active");
        });

        // Hide dropdown when mouse leaves the dropdown or brand
        navContainer.addEventListener("mouseleave", function() {
            timeout = setTimeout(() => {
                navContainer.classList.remove("active");
            }, 300); // Delay closing to prevent accidental closing when moving mouse
        });

        // Prevent dropdown from closing when hovering inside it
        dropdown.addEventListener("mouseenter", function() {
            clearTimeout(timeout);
        });

        dropdown.addEventListener("mouseleave", function() {
            timeout = setTimeout(() => {
                navContainer.classList.remove("active");
            }, 300);
        });
    });
</script>
