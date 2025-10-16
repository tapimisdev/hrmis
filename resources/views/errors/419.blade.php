@extends('errors.layout')

@section('title', '419 Session Expired')

@section('content')
<div class="card">
    <div class="rocket-container">
        <div class="rocket">
            <div class="rocket__nose"></div>
            <div class="rocket__body"></div>
            <div class="flames">
                <div class="flame"></div>
                <div class="flame"></div>
            </div>
            <div class="smoke">
                <div class="particle"></div>
                <div class="particle"></div>
            </div>
        </div>
    </div>

    <div class="error-code">419</div>
    <h1>Session Expired</h1>
    <p>Your session has expired. Please refresh or log in again.</p>
</div>
@endsection
