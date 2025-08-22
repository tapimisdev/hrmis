@extends('auth.auth')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-primary px-3">
    <div class="card shadow-lg border-0 rounded-4" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <h4 class="mt-3 mb-1 fw-semibold">Welcome back!</h4>
                    <p class="text-muted mb-0">Please sign in...</p>
                </div>
                <img src="{{ asset('img/DOST-TAPI.png') }}" alt="logo" class="img-fluid" style="max-height: 40px;">
            </div>

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="form-floating mb-3">
                    <input 
                        type="email" 
                        class="form-control rounded-4 @error('email') is-invalid @enderror" 
                        id="email" 
                        name="email" 
                        placeholder="Username" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="email" 
                        autofocus
                    >
                    <label for="email">Username</label>
                    @error('email')
                        <div class="invalid-feedback d-block">
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <div class="form-floating mb-4">
                    <input 
                        type="password" 
                        class="form-control rounded-4 @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="Password" 
                        required 
                        autocomplete="current-password"
                    >
                    <label for="password">Password</label>
                    @error('password')
                        <div class="invalid-feedback d-block">
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-secondary rounded-4 py-3 d-flex align-items-center justify-content-center">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
