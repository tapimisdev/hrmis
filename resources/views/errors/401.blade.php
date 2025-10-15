@extends('layouts.app')

@section('title', '401 Unauthorized')

@section('content')
<div class="card">
    <div class="rocket-container">
        <div class="rocket">
            <div class="rocket__nose"></div>
            <div class="rocket__body"></div>
            <div class="flames">
                <div class="flame"></div>
                <div class="flame"></div>
                <div class="flame"></div>
            </div>
            <div class="smoke">
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
            </div>
        </div>
    </div>

    <div class="error-code">401</div>
    <h1>Unauthorized</h1>
    <p>You need to log in or have proper credentials to access this page.</p>
</div>
@endsection
