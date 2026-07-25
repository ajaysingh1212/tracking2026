@php
    $currentUser = auth()->user();
    $isPrivileged = $currentUser->hasAnyRole(['Super Admin', 'Admin', 'Manager']);
    $menuSections = $isPrivileged
        ? [
            [
                'label' => 'Main Menu',
                'items' => [
                    ['title' => 'Dashboard', 'icon' => 'fa-solid fa-gauge-high', 'route' => 'dashboard', 'description' => 'Overview and daily status'],
                    ['title' => 'Profile Settings', 'icon' => 'fa-solid fa-user-gear', 'route' => 'profile.edit', 'description' => 'Account and security'],
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    ['title' => 'Users', 'icon' => 'fa-solid fa-users', 'href' => '#', 'description' => 'Manage employee records', 'badge' => 'Soon'],
                    ['title' => 'Roles & Permissions', 'icon' => 'fa-solid fa-user-shield', 'href' => '#', 'description' => 'Access rules and controls', 'badge' => 'Soon'],
                    ['title' => 'Activity Logs', 'icon' => 'fa-solid fa-clock-rotate-left', 'href' => '#', 'description' => 'Recent account and system events', 'badge' => 'Soon'],
                ],
            ],
            [
                'label' => 'Business',
                'items' => [
                    ['title' => 'License Plans', 'icon' => 'fa-solid fa-id-card', 'href' => '#', 'description' => 'Packages and pricing control', 'badge' => 'Soon'],
                    ['title' => 'User Licenses', 'icon' => 'fa-solid fa-file-invoice', 'href' => '#', 'description' => 'Assigned and active plans', 'badge' => 'Soon'],
                    ['title' => 'Notifications', 'icon' => 'fa-solid fa-bell', 'href' => '#', 'description' => 'Broadcasts and alerts', 'badge' => $currentUser->unreadNotifications()->count()],
                ],
            ],
        ]
        : [
            [
                'label' => 'Main Menu',
                'items' => [
                    ['title' => 'Dashboard', 'icon' => 'fa-solid fa-gauge-high', 'route' => 'dashboard', 'description' => 'Your account overview'],
                    ['title' => 'Profile', 'icon' => 'fa-solid fa-user', 'route' => 'profile.edit', 'description' => 'Personal details and password'],
                    ['title' => 'My Licenses', 'icon' => 'fa-solid fa-id-card', 'href' => '#', 'description' => 'Assigned plans and slots', 'badge' => 'Soon'],
                    ['title' => 'Support Center', 'icon' => 'fa-solid fa-headset', 'href' => '#', 'description' => 'Help and tickets', 'badge' => 'Soon'],
                ],
            ],
        ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tracker Enterprise') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hold-transition sidebar-expand-lg layout-fixed app-loaded tracker-shell">
    <div class="app-wrapper">
        @if (session('status'))
            <div data-toast-message="{{ session('status') }}" data-toast-type="success"></div>
        @endif

        <nav class="app-header navbar navbar-expand tracker-topbar border-0">
            <div class="container-fluid px-4">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <a class="nav-link tracker-menu-toggle" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="fas fa-bars"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-sm-flex align-items-center">
                        <div class="tracker-brand-mark">TE</div>
                        <div class="ms-3">
                            <div class="tracker-brand-title">Tracker Enterprise</div>
                            <div class="tracker-brand-subtitle">{{ $isPrivileged ? 'Admin Control Panel' : 'Employee Workspace' }}</div>
                        </div>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item d-none d-xl-block">
                        <div class="tracker-topbar-note">
                            <span class="tracker-topbar-note-label">Live Status</span>
                            <strong>{{ $isPrivileged ? 'Admin Panel Ready' : 'Workspace Active' }}</strong>
                        </div>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <button class="btn tracker-ghost-btn" type="button">
                            <i class="fa-solid fa-lock me-2"></i>Screen Lock
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link tracker-icon-btn" data-bs-toggle="dropdown" href="#">
                            <i class="fa-regular fa-bell"></i>
                            <span class="badge text-bg-primary navbar-badge">{{ $currentUser->unreadNotifications()->count() }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end tracker-dropdown">
                            <div class="dropdown-header fw-semibold">Notifications</div>
                            @forelse ($currentUser->notifications()->latest()->take(5)->get() as $notification)
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item text-wrap small">{{ $notification->data['message'] ?? 'Notification' }}</div>
                            @empty
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item small text-muted">No notifications available.</div>
                            @endforelse
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link tracker-user-toggle" data-bs-toggle="dropdown">
                            <div class="tracker-user-avatar">{{ strtoupper(substr($currentUser->name, 0, 1)) }}</div>
                            <div class="d-none d-md-block">
                                <div class="tracker-user-name">{{ $currentUser->name }}</div>
                                <div class="tracker-user-role">{{ $currentUser->getRoleNames()->first() ?? 'User' }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-down small text-muted"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end tracker-dropdown p-2">
                            <li class="px-2 py-2 border-bottom">
                                <div class="fw-semibold">{{ $currentUser->name }}</div>
                                <div class="small text-muted">{{ $currentUser->email }}</div>
                            </li>
                            <li><a class="dropdown-item rounded-3" href="{{ route('profile.edit') }}">Profile Settings</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item rounded-3 text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar tracker-sidebar elevation-0">
            <a href="{{ route('dashboard') }}" class="brand-link tracker-brand-link">
                <div class="tracker-sidebar-logo">TE</div>
                <div>
                    <div class="tracker-sidebar-title">Tracker Enterprise</div>
                    <div class="tracker-sidebar-caption">Operational Dashboard</div>
                </div>
            </a>

            <div class="sidebar-wrapper">
            <div class="sidebar">
                <div class="tracker-profile-card">
                    <div class="tracker-profile-avatar">{{ strtoupper(substr($currentUser->name, 0, 1)) }}</div>
                    <div>
                        <div class="tracker-profile-name">{{ $currentUser->name }}</div>
                        <div class="tracker-profile-role">{{ $currentUser->getRoleNames()->first() ?? 'User' }}</div>
                    </div>
                </div>

                <div class="tracker-sidebar-panel">
                    <div class="tracker-sidebar-panel-label">Workspace</div>
                    <div class="tracker-sidebar-panel-value">{{ $isPrivileged ? 'AdminLTE Style Console' : 'User Dashboard' }}</div>
                </div>

                <div class="tracker-sidebar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" class="form-control border-0 shadow-none" placeholder="Search menu">
                </div>

                <nav class="mt-4">
                    <ul class="nav sidebar-menu flex-column tracker-sidebar-nav" data-lte-toggle="treeview" role="menu">
                        @foreach ($menuSections as $section)
                            <li class="nav-header">{{ $section['label'] }}</li>
                            @foreach ($section['items'] as $item)
                                @php
                                    $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
                                    $url = isset($item['route']) ? route($item['route']) : ($item['href'] ?? '#');
                                @endphp
                                <li class="nav-item">
                                    <a href="{{ $url }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                                        <span class="tracker-nav-icon-wrap">
                                            <i class="nav-icon {{ $item['icon'] }}"></i>
                                        </span>
                                        <div class="tracker-nav-copy">
                                            <p>{{ $item['title'] }}</p>
                                            @if (! empty($item['description']))
                                                <span>{{ $item['description'] }}</span>
                                            @endif
                                        </div>
                                        @if (isset($item['badge']) && $item['badge'] !== 0 && $item['badge'] !== '0')
                                            <small class="tracker-nav-badge">{{ $item['badge'] }}</small>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </nav>
            </div>
            </div>
        </aside>

        <main class="app-main tracker-content-wrapper">
            <section class="app-content-header tracker-page-header">
                <div class="container-fluid">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <div class="tracker-page-eyebrow">{{ $isPrivileged ? 'Administration' : 'Workspace' }}</div>
                            <h1 class="tracker-page-title mb-0">{{ $header ?? 'Dashboard' }}</h1>
                        </div>
                        <ol class="breadcrumb tracker-breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">{{ $header ?? 'Dashboard' }}</li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="app-content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </section>
        </main>

        <footer class="app-footer tracker-footer border-0">
            <strong>{{ now()->year }} Tracker Enterprise</strong>
            <div class="float-end d-none d-sm-inline-block">Employee Tracking SaaS</div>
        </footer>
    </div>
</body>
</html>
