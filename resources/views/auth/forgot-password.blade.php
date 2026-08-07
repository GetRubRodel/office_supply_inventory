@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <h2>Forgot Password</h2>
    <p class="sub">Enter your registered email address to receive a verification code.</p>

    <form method="POST" action="{{ route('password.otp.send') }}">
        @csrf

        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>

        <button type="submit" class="primary">Send Verification Code</button>
    </form>

    <div class="link-row">
        <a href="{{ route('filament.admin.auth.login') }}">Back to login</a>
    </div>
@endsection
