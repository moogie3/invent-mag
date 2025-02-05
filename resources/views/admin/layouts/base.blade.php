<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Invent-Mag | @yield('title')</title>
    <link href="{{asset('tabler/dist/css/tabler.min.css?1692870487')}}" rel="stylesheet" />
    <link href="{{asset('tabler/dist/css/tabler-flags.min.css?1692870487')}}" rel="stylesheet" />
    <link href="{{asset('tabler/dist/css/tabler-payments.min.css?1692870487')}}" rel="stylesheet" />
    <link href="{{asset('tabler/dist/css/tabler-vendors.min.css?1692870487')}}" rel="stylesheet" />
    <link href="{{asset('tabler/dist/css/demo.min.css?1692870487')}}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>

<body>
    <script>
        // Set theme based on session or default to light
        document.addEventListener('DOMContentLoaded', function () {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }

            // Event listener for theme toggle
            const darkModeButton = document.querySelector('a[href="?theme=dark"]');
            const lightModeButton = document.querySelector('a[href="?theme=light"]');

            darkModeButton.addEventListener('click', () => {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            });

            lightModeButton.addEventListener('click', () => {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            });
        });
    </script>
    @include('admin.layouts.navbar')
    <div class="main-content">
        @yield('content')
    </div>
    @include('admin.layouts.footer')
    @include('admin.layouts.script')
</body>

</html>
