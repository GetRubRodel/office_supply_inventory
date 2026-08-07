@extends('layouts.guest')

@section('title', 'Verify OTP')

@section('content')
    <h2>Enter Verification Code</h2>
    <p class="sub">Enter the 6-digit code sent to {{ $email }}.</p>

    <form method="POST" action="{{ route('password.otp.verify.submit') }}">
        @csrf

        <div class="field">
            <label for="otp_code">Verification Code</label>
            <input type="text" id="otp_code" name="otp_code" class="otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" value="{{ old('otp_code') }}" required autofocus autocomplete="one-time-code">
            <p class="hint">The code is valid for only 5 seconds.</p>
        </div>

        <button type="submit" class="primary">Verify Code</button>
    </form>

    <form method="POST" action="{{ route('password.otp.resend') }}" class="link-row">
        @csrf
        <button type="submit" class="link">Resend OTP</button>
    </form>

    <div class="link-row">
        <a href="{{ route('password.request') }}">Start over</a>
    </div>
@endsection
