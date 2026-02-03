@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="form-container border shadow">
        <div class='logo-container'>
            <img src="{{ asset('img/dost-tapi.png') }}" alt="">
            <div class="seperator"></div>
            <div class="form-header">
                <h5>Confirm Your Password</h5>
                <p>Please confirm your password before continuing.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" novalidate>
            @csrf

            <div class="w-100 mb-4">
                <input 
                    id="password" 
                    type="password" 
                    class="form-input @error('password') is-invalid @enderror" 
                    name="password" 
                    placeholder="Password" 
                    required 
                    autocomplete="current-password"
                >
                @error('password')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="auth-btn">
                    Confirm Password
                </button>
            </div>

            @if (Route::has('password.request'))
                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="link">
                        Forgot Your Password?
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection
