<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.0/moment.min.js"></script>
    <script>
        (function () {
            const storageKey = 'theme-preference';
            const storedTheme = localStorage.getItem(storageKey);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();

        (function () {
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            document.addEventListener('DOMContentLoaded', () => {
                const sidebar = document.querySelector('.sidebar');
                const app = document.getElementById('app');

                if (collapsed && sidebar) {
                    sidebar.classList.add('collapsed');
                    app?.classList.add('sidebar-collapsed');
                }
            });
        })();
    </script>
    @yield('styles')
    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js',
        'resources/sass/employee.scss',
    ])
</head>

<body>
    <div class="top-space"></div>

    <div id="app">
        <div class="top" style="background-image: url('{{ asset('img/tapi-front.png') }}');"></div>

        @include('employee.components.sidebar')

        <main>
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

                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content shadow-lg border-0 rounded-4">
                        <div class="modal-body px-4 p-5">

                            <div class="row g-4 align-items-start">

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h4 class="fw-semibold mb-1">📺 Full Video Tutorial</h4>
                                        <p class="text-muted small mb-3">
                                            Please watch this quick guide before updating your password.
                                        </p>
                                    </div>

                                    <div class="rounded overflow-hidden shadow-sm" style="padding:53.13% 0 0 0;position:relative;">
                                        <iframe
                                            src="https://player.vimeo.com/video/1158272662?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479"
                                            frameborder="0"
                                            allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin"
                                            style="position:absolute;top:0;left:0;width:100%;height:100%;"
                                            title="ORBIT ESS"></iframe>
                                    </div>

                                    <div class="alert alert-warning d-flex align-items-start gap-2 small mt-3 rounded shadow-sm" role="alert">
                                        <span class="fs-5">📌</span>
                                        <div>
                                            <strong>Important Reminder</strong><br>
                                            Kindly finish watching the video tutorial first to ensure a smooth password update process.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="alert alert-info d-flex align-items-start gap-2 small mb-4 rounded shadow-sm" role="alert">
                                        <span class="fs-5">🔐</span>
                                        <div>
                                            <strong>Security Update Required</strong><br>
                                            For your protection, you are required to update your password now to prevent unauthorized access.
                                        </div>
                                    </div>

                                    <change-password
                                        title="Update Password"
                                        @password-changed="handlePasswordChanged">
                                    </change-password>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <push-notification :user-role="'employee'" :user-id='@json(Auth::id())'></push-notification>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.umd.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.15/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/party-js@latest/bundle/party.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

    @yield('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const app = document.getElementById('app');
            const sidebar = document.querySelector('.sidebar');
            const buttons = [
                document.getElementById('switchMenuBtn'),
                document.getElementById('imgSwitchBtn')
            ];

            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            if (collapsed) {
                sidebar?.classList.add('collapsed');
                app?.classList.add('sidebar-collapsed');
                buttons.forEach(btn => btn?.classList.add('rotate'));
            }

            buttons.forEach(btn => btn?.addEventListener('click', () => {
                sidebar?.classList.toggle('collapsed');
                app?.classList.toggle('sidebar-collapsed');
                buttons.forEach(b => b?.classList.toggle('rotate'));
                localStorage.setItem('sidebarCollapsed', sidebar?.classList.contains('collapsed') ? 'true' : 'false');
            }));

            $('.x-mark').on('click', function () {
                const $aside = $('aside');
                const $overlay = $('.sidebar-overlay');

                if ($aside.length) $aside.toggleClass('mobile-open');
                if ($overlay.length) $overlay.toggleClass('active');
            });
        });
    </script>
    <script>
        @if(session('auth_token'))
            localStorage.setItem('auth_token', @json(session('auth_token')));
            localStorage.setItem('name', @json(session('name')));
            localStorage.setItem('email', @json(session('email')));
            localStorage.setItem('session_id', @json(session('session_id')));

            @php
                session()->forget('auth_token');
                session()->forget('name');
                session()->forget('email');
                session()->forget('session_id');
            @endphp
        @endif
    </script>
</body>
</html>