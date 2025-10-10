@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="card-container">
        <div class="form-container">
            <div class="logo">
                <img src="{{ asset('img/HR EN ROLL.png') }}" alt="">
            </div>

            <h5>Sign In</h5>
            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="form-floating mb-3">
                    <input 
                        type="email" 
                        class="form-input @error('email') is-invalid @enderror" 
                        id="email" 
                        name="email" 
                        placeholder="Username" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="email" 
                        autofocus
                    >
                    @error('email')
                        <div class="invalid-feedback d-block">
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <div class="form-floating mb-2">
                    <input 
                        type="password" 
                        class="form-input @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="Password" 
                        required 
                        autocomplete="current-password"
                    >
                    @error('password')
                        <div class="invalid-feedback d-block">
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap mx-2 justify-content-between align-items-center mb-3">
                    {{-- Remember Me --}}
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember" style="font-size: 14px; color: #2c3e50;">
                            Remember Me
                        </label>
                    </div>

                    {{-- Forgot Password --}}
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size: 14px; color: #0c8384;">
                            Forgot Password?
                        </a>
                    @endif
                </div>


                <div class="d-grid">
                    <button type="submit" class="btn btn-primary rounded-5 py-3 d-flex align-items-center justify-content-center">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
