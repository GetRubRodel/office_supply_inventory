<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account Access') — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #fafafa;
            color: #18181b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        .brand { text-align: center; margin-bottom: 1.5rem; }
        .brand .logo { height: 3rem; margin-bottom: 0.5rem; }
        .brand h1 { font-size: 0.95rem; font-weight: 700; color: #111827; line-height: 1.4; }
        .card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; text-align: center; }
        .card .sub { color: #71717a; font-size: 0.875rem; text-align: center; margin-bottom: 1.25rem; line-height: 1.5; }
        .alert {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .alert-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .errors { list-style: none; margin: 0; }
        .errors li { margin-bottom: 0.25rem; }
        .errors li:last-child { margin-bottom: 0; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem; }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid #d4d4d8;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
        .field { margin-bottom: 1rem; }
        .hint { font-size: 0.8rem; color: #71717a; margin-top: 0.375rem; }
        button.primary {
            width: 100%;
            background: #f59e0b;
            color: #fff;
            font-weight: 600;
            padding: 0.625rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.15s;
        }
        button.primary:hover { background: #d97706; }
        .link-row { text-align: center; margin-top: 1rem; font-size: 0.875rem; }
        .link-row a { color: #b45309; text-decoration: none; font-weight: 500; }
        .link-row a:hover { text-decoration: underline; }
        button.link {
            background: none;
            border: none;
            color: #b45309;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }
        button.link:hover { text-decoration: underline; }
        .otp { font-size: 1.5rem; letter-spacing: 0.4em; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="System Logo" class="logo">
            <h1>Web-Based Supply Management and Procurement System</h1>
        </div>

        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul class="errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
