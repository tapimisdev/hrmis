<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} | Messages</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            const storageKey = 'theme-preference';
            const storedTheme = localStorage.getItem(storageKey);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();

        @if(session('auth_token'))
            localStorage.setItem('auth_token', @json(session('auth_token')));
            localStorage.setItem('name', @json(session('name')));
            localStorage.setItem('email', @json(session('email')));
            localStorage.setItem('session_id', @json(session('session_id')));
        @endif
    </script>
    @yield('styles')
    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js',
        'resources/sass/employee.scss',
    ])
    <style>
        html, body {
            min-height: 100%;
        }

        body {
            margin: 0;
            overflow: hidden;
            background:
                radial-gradient(circle at 20% 20%, rgba(255, 44, 131, 0.16), transparent 28%),
                radial-gradient(circle at 80% 30%, rgba(139, 37, 169, 0.2), transparent 26%),
                linear-gradient(145deg, #120913 0%, #1d1120 35%, #2a1030 100%);
            color: #f7edf8;
        }

        #app {
            width: 100vw;
            min-height: 100vh;
        }

        .messages-layout {
            width: 100vw;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="messages-layout">
            @yield('content')
        </div>

        <push-notification :user-role="'employee'" :user-id='@json(Auth::id())'></push-notification>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    @yield('scripts')

    @if(session('auth_token'))
        @php
            session()->forget('auth_token');
            session()->forget('name');
            session()->forget('email');
            session()->forget('session_id');
        @endphp
    @endif
</body>
</html>
