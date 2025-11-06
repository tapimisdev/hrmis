@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="card-container">
        <div class="form-container">
            <div class="logo">
                <img src="{{ asset('img/HR_NROLL.png') }}" alt="">
            </div>

            <h5>{{ __('Reset Password') }}</h5>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                
            <div class="form-floating">
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
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary rounded-5 py-3 d-flex align-items-center justify-content-center">
                        {{ __('Send Password Reset Link') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
