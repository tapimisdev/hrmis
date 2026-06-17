<!doctype html>
<html lang="en"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>Careers - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand-lg careers-navbar"><div class="container">
    <a class="navbar-brand fw-bold" href="{{ route('careers.jobs') }}">{{ config('app.name') }} Careers</a>
    <div class="ms-auto d-flex gap-2">
        @auth
            <a class="btn btn-sm btn-primary" href="{{ route('applicant.dashboard') }}">My Applications</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Logout</button></form>
        @else
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">Login</a><a class="btn btn-sm btn-primary" href="{{ route('applicant.register') }}">Register</a>
        @endauth
    </div>
</div></nav>
<main id="applicant-app">@yield('content')</main>
<style>
    body {
        margin: 0;
        background: #f5f7fb;
        color: #172033;
        font-family: "Montserrat", sans-serif;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .careers-navbar {
        position: sticky;
        top: 0;
        z-index: 20;
        border-bottom: 1px solid #dbe3ee;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
    }

    .careers-navbar .navbar-brand {
        color: #172033;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
@vite('resources/js/applicant/recruitment.js')
</body></html>
