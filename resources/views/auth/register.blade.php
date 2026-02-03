@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="form-container border shadow">
        <div class='logo-container'>
            <img src="{{ asset('img/dost-tapi.png') }}" alt="">
            <div class="seperator"></div>
            <div class="form-header">
                <h5>Create an Account</h5>
                <p>Join us today and start your journey!</p>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf

            <div class="w-100 mb-3">
                <input 
                    id="name" 
                    type="text" 
                    class="form-input @error('name') is-invalid @enderror" 
                    name="name" 
                    value="{{ old('name') }}" 
                    placeholder="Full Name" 
                    required 
                    autocomplete="name" 
                    autofocus
                >
                @error('name')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="w-100 mb-3">
                <input 
                    id="email" 
                    type="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    name="email" 
                    value="{{ old('email') }}" 
                    placeholder="Email Address" 
                    required 
                    autocomplete="email"
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
                    placeholder="Password" 
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
                    placeholder="Confirm Password" 
                    required 
                    autocomplete="new-password"
                >
            </div>

            <div class="d-grid">
                <button type="submit" class="auth-btn">
                    Register
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
