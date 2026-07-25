<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tracker Enterprise') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hold-transition login-page">
    <div class="login-box tracker-auth-card">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="{{ url('/') }}" class="h3 text-decoration-none"><b>Tracker</b> Enterprise</a>
            </div>
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
