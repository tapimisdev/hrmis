@extends('errors.layout')

@section('title', '404 Page Not Found')

@section('content')
<div class="card">
    <!-- Rocket container -->
    <div class="rocket-container">
        <div class="rocket">
            <!-- Nose cone -->
            <div class="rocket__nose"></div>
            
            <!-- Body -->
            <div class="rocket__body"></div>
            
            <!-- Flames -->
            <div class="flames">
            <div class="flame"></div>
            <div class="flame"></div>
            <div class="flame"></div>
            </div>
            
            <!-- Smoke particles -->
            <div class="smoke">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            </div>
        </div>
    </div>
    <div class="error-code">404</div>
    <h1>Page Not Found!</h1>
    <p>The page you are looking for doesn't exist or has been moved.</p>
</div>
@endsection
