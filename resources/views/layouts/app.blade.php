<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') · {{ config('app.name', 'Tracker Enterprise') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="hold-transition sidebar-expand-lg layout-fixed app-loaded tracker-shell">
    <div class="app-wrapper">
        @if (session('status'))
            <div data-toast-message="{{ session('status') }}" data-toast-type="success"></div>
        @endif

        @if (session('error'))
            <div data-toast-message="{{ session('error') }}" data-toast-type="error"></div>
        @endif

        @include('layouts.partials.topbar')

        @include('layouts.partials.sidebar')

        <main class="app-main tracker-content-wrapper">
            <section class="app-content-header tracker-page-header">
                <div class="container-fluid">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <div class="tracker-page-eyebrow">@yield('page-eyebrow', 'Workspace')</div>
                            <h1 class="tracker-page-title mb-0">@yield('page-title', 'Dashboard')</h1>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @hasSection('page-actions')
                                <div class="tracker-page-actions d-flex gap-2">
                                    @yield('page-actions')
                                </div>
                            @endif
                            <ol class="breadcrumb tracker-breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                @yield('breadcrumb')
                                <li class="breadcrumb-item active">@yield('page-title', 'Dashboard')</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="app-content">
                <div class="container-fluid">
                    @if ($errors->any())
                        <div class="alert alert-danger tracker-alert">
                            <strong>Please fix the following:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </section>
        </main>

        @include('layouts.partials.footer')
    </div>

    @stack('scripts')
</body>
</html>
