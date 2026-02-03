@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="form-container border shadow">
        <div class='logo-container'>
            <img src="{{ asset('img/dost-tapi.png') }}" alt="">
            <div class="seperator"></div>
            <div class="form-header">
                <h5>Forgot Your Password?</h5>
                <p>Enter your email and we’ll send a reset link.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success d-block mb-3" role="alert">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf

            <div class="w-100 mb-4">
                <input 
                    type="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    id="email" 
                    name="email" 
                    placeholder="Email Address" 
                    value="{{ old('email') }}" 
                    required 
                    autocomplete="email" 
                    autofocus
                >
                @error('email')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="auth-btn">
                    Send Reset Link
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
