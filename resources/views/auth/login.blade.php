@extends('auth.auth')

@section('content')
<div class="wrapper p-3">
    <div class="form-container border shadow">
        <div class='logo-container'>
            <img src="{{ asset('img/dost-tapi.png') }}" alt="">
            <div class="seperator"></div>
            <div class="form-header">
               <h5>Welcome Back, Genius!</h5>
                <p>Log in and let the magic happen.</p>
            </div>
        </div class='logo-container'>
        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <div class="w-100 mb-3">
                <input 
                    type="text" 
                    class="form-input @error('email') is-invalid @enderror" 
                    id="email" 
                    name="email" 
                    placeholder="Email or Employee No." 
                    value="{{ old('email', request()->cookie('remember_email')) }}"
                    required 
                    autocomplete="username" 
                    autofocus
                >
                @error('email')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="w-100 mb-4">
                <input 
                    type="password" 
                    class="form-input @error('password') is-invalid @enderror" 
                    id="password" 
                    name="password" 
                    placeholder="Password" 
                    required 
                    value="{{ request()->cookie('remember_password') ? decrypt(request()->cookie('remember_password')) : '' }}"
                    autocomplete="current-password"
                >
                @error('password')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="d-flex flex-wrap mx-2 justify-content-between align-items-center mb-4">
                {{-- Remember Me --}}
                <div class="d-flex gap-1">
                    <input class="" type="checkbox" name="remember" id="remember" {{ (old('remember') || request()->cookie('remember_email')) ? 'checked' : '' }}>
                    <label for="remember">
                        Remember Me
                    </label>
                </div>

                {{-- Forgot Password --}}
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link">
                        Forgot Password?
                    </a>
                @endif
            </div>

            <div class="d-grid">
                <button type="submit" class="auth-btn">
                    Sign In
                </button>
            </div>
        </form>
    </div>
    <div class="toggle">
        <ul>
            <li><a href="">DTOMS</a></li>
            <li><a href="">DTORS</a></li>
        </ul>
        <div class="toggle-container">
            <button class="theme-toggle" id="theme-toggle" title="Toggles light & dark" aria-label="light" aria-live="polite">
                <div class="toggle-icon sun">
                    <svg viewBox="0 q0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5" fill="#FFD700" stroke="#FFD700"/>
                        <line x1="12" y1="1" x2="12" y2="3" stroke="#FFD700"/>
                        <line x1="12" y1="21" x2="12" y2="23" stroke="#FFD700"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" stroke="#FFD700"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" stroke="#FFD700"/>
                        <line x1="1" y1="12" x2="3" y2="12" stroke="#FFD700"/>
                        <line x1="21" y1="12" x2="23" y2="12" stroke="#FFD700"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" stroke="#FFD700"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" stroke="#FFD700"/>
                    </svg>
                </div>
                <div class="toggle-icon moon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" fill="#93C5FD" stroke="#93C5FD"/>
                    </svg>
                </div>
            </button>
            <div class="tooltip">
                <span class="tooltip-text"></span>
            </div>
        </div>
    </div>
</div>
@endsection
