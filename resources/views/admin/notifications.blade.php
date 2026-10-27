@extends('admin.layouts.base')

@section('title', __('messages.my_notifications'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.notification.page-body')
    </div>
    
    @include('admin.layouts.modals.general.clear-notifications-modal')

    <style>
        .notification-item.transition-all {
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        .notification-item.transition-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important;
            border-color: #ddd !important;
        }
        .mark-as-read-btn {
            opacity: 0.6;
            transition: all 0.2s ease;
        }
        .notification-item:hover .mark-as-read-btn {
            opacity: 1;
        }
        .mark-as-read-btn:hover {
            color: #d63939;
            background-color: rgba(214, 57, 57, 0.1);
            border-color: transparent;
        }
        .z-index-2 {
            z-index: 2;
        }

        /* Dark mode compatibility - frosted neutral ladder: items sit recessed
           in the card (#1c1e20 body rung), hover lifts to #26282a. The slate
           navies (#1e293b / #273449) were retired with the dark blue theme. */
        [data-bs-theme="dark"] .notification-item {
            background-color: #1c1e20 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f3f5;
        }
        [data-bs-theme="dark"] .notification-item.transition-all:hover {
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.3) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            background-color: #26282a !important;
        }
        [data-bs-theme="dark"] .text-reset {
            color: #f1f3f5 !important;
        }
        [data-bs-theme="dark"] .text-secondary,
        [data-bs-theme="dark"] .text-muted {
            color: #a8b0b8 !important;
        }
        [data-bs-theme="dark"] .mark-as-read-btn {
            color: #a8b0b8;
            border-color: rgba(255, 255, 255, 0.1);
        }
        [data-bs-theme="dark"] .mark-as-read-btn:hover {
            color: #ef6b6b;
            background-color: rgba(214, 57, 57, 0.15);
        }
        /* Color variants for dark mode list items */
        [data-bs-theme="dark"] .bg-primary-lt { background-color: rgba(32, 107, 196, 0.15) !important; color: #4d8fd6 !important; }
        [data-bs-theme="dark"] .bg-danger-lt { background-color: rgba(214, 57, 57, 0.15) !important; color: #ef6b6b !important; }
        [data-bs-theme="dark"] .bg-warning-lt { background-color: rgba(245, 159, 0, 0.15) !important; color: #f7b740 !important; }
        [data-bs-theme="dark"] .bg-info-lt { background-color: rgba(13, 202, 240, 0.15) !important; color: #5bcaf0 !important; }
        [data-bs-theme="dark"] .bg-success-lt { background-color: rgba(47, 179, 68, 0.15) !important; color: #5cc86e !important; }

        /* Expiring label colors - explicit for both modes */
        .expiring-label-danger { color: #d63939; }
        .expiring-label-warning { color: #f76707; }
        [data-bs-theme="dark"] .expiring-label-danger { color: #ef6b6b; }
        [data-bs-theme="dark"] .expiring-label-warning { color: #f98a42; }

        /* Status icon colors in avatar */
        .notification-item .avatar i {
            color: #333 !important;
        }
        [data-bs-theme="dark"] .notification-item .avatar i {
            color: #fff !important;
        }
    </style>

    {{-- Config for resources/js/admin/notifications.js --}}
    <div id="notifications-config"
         data-mark-read-base-url="{{ url('/admin/notifications/mark-read') }}"
         data-clear-all-url="{{ route('admin.notifications.clear-all') }}"
         class="d-none"></div>
@endsection
