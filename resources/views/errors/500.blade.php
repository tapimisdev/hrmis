@extends('errors.layout')

@section('content')
<div class="container-fluid">
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
    <div class="error-code">500</div>
    <h1>Server Error</h1>
    <p>Houston, we have a problem! Our servers are currently down.</p>
  </div>
</div>
@endsection