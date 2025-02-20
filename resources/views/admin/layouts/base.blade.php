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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
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
