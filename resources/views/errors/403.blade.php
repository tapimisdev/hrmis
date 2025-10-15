@extends('layouts.app')

@section('title', '403 Forbidden')

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

    <div class="error-code">403</div>
    <h1>Access Denied 🚫</h1>
    <p>Sorry, you don’t have permission to view this page.</p>
</div>
@endsection
