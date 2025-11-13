@extends('errors.layout')

@section('title', '500 Internal Server Error')

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
    <div class="error-code">503</div>
    <h1>Server Napping</h1>
    <p>Even servers need a nap. Hang tight, we'll be awake soon!</p>
  </div>
</div>
@endsection