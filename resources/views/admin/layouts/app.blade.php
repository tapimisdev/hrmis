<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token (used for form security in Laravel) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic page title -->
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts replacement (Nunito from Bunny.net for faster CDN) -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome 6 (icon library) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap 5.3 core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables Bootstrap 5 styling -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- LightGallery CSS (image gallery / lightbox support) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lightgallery-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.8.3/css/lightgallery.min.css">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">

    <!-- Select2 CSS (for searchable select dropdowns) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css"
        />
        
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- Fancybox CSS (lightbox for images/videos) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css" />

    <!-- SweetAlert2 (for modern alert and confirmation modals) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

    <!-- Extra styles pushed from child views -->
    @yield('styles')
    @stack('styles')

    <!-- Vite (compiles and loads local SCSS/JS assets) -->
    @vite([
        'resources/sass/app.scss', 
        'resources/js/app.js', 
        'resources/sass/style.scss', 
        'resources/sass/dashboard.scss'
    ])
</head>
<body>
    <div id="app">
        <!-- Sidebar (admin navigation) -->
        @include('admin.components.sidebar')

        <main>
            <!-- Top navbar -->
            @include('admin.components.navbar')

            <!-- Main content area (unique per page) -->
            @yield('content')
        </main>
    </div>

    <!-- jQuery 3.6 (dependency for many plugins) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Axios (for making HTTP requests, e.g., AJAX calls) -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Select2 JS (enhanced select dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- DataTables core JS (sorting, searching, pagination for tables) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <!-- DataTables Bootstrap 5 integration -->
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Fancybox (lightbox for images, videos, and inline content) -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.umd.js"></script>

    <script>
        @if(session('auth_token'))
            localStorage.setItem('auth_token', "{{ session('auth_token') }}");
            console.log("{{ session('auth_token') }}");
            
            // Clear it from session
            {{ session()->forget('auth_token') }}
        @endif
    </script>

    <!-- Extra scripts pushed from child views -->
    @yield('scripts')
</body>
</html>