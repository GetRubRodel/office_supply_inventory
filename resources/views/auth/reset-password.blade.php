@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <h2>Reset Password</h2>
    <p class="sub">Enter a new password for {{ $email }}.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <div class="field">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
            <p class="hint">Minimum 8 characters.</p>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="primary">Reset Password</button>
    </form>
@endsection
