<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bunny Fonts (Nunito font family) -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Select2 CSS (for enhanced select dropdowns) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Font Awesome 6.5.0 (for icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap 5.3.0 CSS (core layout & responsive design) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables Bootstrap 5.3.0 CSS (for styled data tables) -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- FullCalendar CSS (for calendar views) -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">

    <!-- Fancybox UI CSS (for image/content lightbox modals) -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css"
    />

    <!-- Favicon icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">

    <!-- SweetAlert2 (for modern alert dialogs) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // init-theme-sidebar.js

        (function () {
            //  Theme setup
            const storageKey = 'theme-preference';
            const storedTheme = localStorage.getItem(storageKey);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();

        (function () {
            // Sidebar collapse setup
            const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            const sidebar = document.querySelector('.sidebar');

            if (collapsed && sidebar) {
                sidebar.classList.add('collapsed');
            }
        })();

    </script>

    @yield('styles')

    <!-- Vite compiled assets (SASS + JS) -->
    @vite([
        'resources/sass/app.scss', 
        'resources/js/app.js',
        'resources/sass/employee.scss',
    ])
</head>
<body>
    <div class="top-space"></div>
    <div id="app">
        <div class="top" style="background-image: url('{{ asset('img/tapi-front.png') }}');">
        </div>
        @include('employee.components.sidebar')
        <main>
            <!-- to incomplete birthdays -->
            <birthday-component></birthday-component>
            <div>
                 @yield('content')
            </div>
            @include('employee.components.footer')
            <div class="modal fade" 
                id="forceChangePasswordModal" 
                tabindex="-1" 
                aria-hidden="true"
                data-bs-backdrop="static"
                data-bs-keyboard="false">

                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content shadow-lg border-0 rounded-4">
                        <div class="modal-body px-4 p-5">
                            <div class="alert alert-info small mb-4" role="alert">
                                As part of our ongoing commitment to account security, you are required to update your password at this time. 
                                This helps protect your account from unauthorized access.
                            </div>
                            <change-password title='Update Password' @password-changed="handlePasswordChanged"></change-password>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- jQuery 3.6 (dependency for many JS plugins) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Axios (for HTTP requests and API calls) -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Select2 JS (for enhanced select dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- DataTables Core + Bootstrap Integration JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Fancybox JS (for lightbox-style image and modal popups) -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.umd.js"></script>

    <!-- FullCalendar JS (for dynamic calendar events) -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/party-js@latest/bundle/party.min.js"></script>

    @yield('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const app = document.getElementById('app');
            const sidebar = document.querySelector('.sidebar');
            const buttons = [document.getElementById('switchMenuBtn'), document.getElementById('imgSwitchBtn')];
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            // Apply saved state on load
            if (collapsed) {
                sidebar.classList.add('collapsed');
                app.classList.add('sidebar-collapsed');
                buttons.forEach(btn => btn?.classList.add('rotate'));
            }

            // Toggle sidebar on button click
            buttons.forEach(btn => btn?.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                app.classList.toggle('sidebar-collapsed');
                btn.classList.toggle('rotate');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }));

            $('.x-mark').on('click', function() {
                const $aside = $('aside');
                const $overlay = $('.sidebar-overlay');

                if ($aside.length) $aside.toggleClass('mobile-open');
                if ($overlay.length) $overlay.toggleClass('active');
            });
        });
    </script>

     <script>
        @if(session('auth_token'))
            localStorage.setItem('auth_token', "{{ session('auth_token') }}");
            localStorage.setItem('name', "{{ session('name') }}");
            localStorage.setItem('email', "{{ session('email') }}");
            {{ session()->forget('auth_token') }}
            {{ session()->forget('name') }}
            {{ session()->forget('email') }}
        @endif
    </script>

</body>
</html>
