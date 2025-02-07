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
    @stack('styles')
    <style>

        body .dataTables_filter input {
            color: black !important;  /* Dark text for light mode */
            background-color: #fff !important;  /* Light background */
            border: 1px solid #bbb !important;
            margin-bottom: 20px;
        }

        body.light-mode td, th {
            background-color: #5d62714b !important;
        }

        body td,th {
            text-align:center;
        }

        /* Change DataTables search bar text color */
        body.dark-mode .dataTables_filter input {
            color: white !important;
            background-color: #5d62714b !important;
            border: 1px solid #bbb !important;
            margin-bottom: 20px;
        }

        body.dark-mode .dataTables_filter input::placeholder {
            color: rgba(255, 255, 255, 0) !important;
        }

        body.dark-mode .dataTables_filter label {
            color: white !important;
        }

        body.dark-mode .dataTables_length label {
            color: white !important;
        }

        body.dark-mode .dataTables_info {
            color: white !important;
        }

        body.dark-mode table tbody{
            border: 1px solid gray !important;
            color: black !important;
        }

        body.dark-mode td, th {
            text-align: center;
            background-color:#5d62714b;
        }
    </style>
</head>

<body>
    @include('admin.layouts.navbar')
    <div class="main-content">
        @yield('content')
    </div>
    @include('admin.layouts.footer')
    @include('admin.layouts.script')
</body>

</html>
