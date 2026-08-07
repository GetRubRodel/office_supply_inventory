@extends('layouts.guest')

@section('title', 'Password Reset Successful')

@section('content')
    <h2>Password Reset Successful</h2>
    <p class="sub">Your password has been updated. You can now log in with your new password.</p>

    <div class="link-row">
        <a href="{{ route('filament.admin.auth.login') }}">Go to Login</a>
    </div>
@endsection
