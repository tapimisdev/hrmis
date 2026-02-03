@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="form-container border shadow">
        <div class='logo-container'>
            <img src="{{ asset('img/dost-tapi.png') }}" alt="">
            <div class="seperator"></div>
            <div class="form-header">
                <h5>Reset Your Password</h5>
                <p>Choose a strong new password to secure your account.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="w-100 mb-3">
                <input 
                    id="email" 
                    type="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    name="email" 
                    value="{{ $email ?? old('email') }}" 
                    placeholder="Email Address" 
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

            <div class="w-100 mb-3">
                <input 
                    id="password" 
                    type="password" 
                    class="form-input @error('password') is-invalid @enderror" 
                    name="password" 
                    placeholder="New Password" 
                    required 
                    autocomplete="new-password"
                >
                @error('password')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="w-100 mb-4">
                <input 
                    id="password-confirm" 
                    type="password" 
                    class="form-input" 
                    name="password_confirmation" 
                    placeholder="Confirm New Password" 
                    required 
                    autocomplete="new-password"
                >
            </div>

            <div class="d-grid">
                <button type="submit" class="auth-btn">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
