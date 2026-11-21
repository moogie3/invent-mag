{{-- Tabler JS, served from public/ so the layout never waits on a CDN. --}}
<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}"></script>

{{-- Global Notification Helper --}}
@vite(['resources/js/admin/helpers/notification.js', 'resources/js/superadmin/layouts/notifications.js'])

{{-- Bootstrap re-initialization for Turbo --}}
@vite('resources/js/admin/bootstrap-init.js')
