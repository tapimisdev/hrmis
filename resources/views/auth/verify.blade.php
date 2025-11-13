@extends('auth.auth')

@section('content')
<div class="wrapper">
    <div class="form-container border shadow">
        <div class='logo-container'>
            <img src="{{ asset('img/orbit.png') }}" alt="">
            <div class="seperator"></div>
            <div class="form-header">
                <h5>Verify Your Email</h5>
                <p>Before proceeding, please check your email for a verification link.</p>
            </div>
        </div>

        @if (session('resent'))
            <div class="alert alert-success d-block mb-3" role="alert">
                A fresh verification link has been sent to your email address.
            </div>
        @endif

        <p>If you did not receive the email, you can request another one:</p>

        <form method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <div class="d-grid">
                <button type="submit" class="auth-btn">
                    Resend Verification Email
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
