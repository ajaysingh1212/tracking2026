@php
    $__menuSections = config('sidebar_menu', []);
    $__unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<ul class="nav sidebar-menu flex-column tracker-sidebar-nav" data-lte-toggle="treeview" role="menu">
    @foreach ($__menuSections as $section)
        @php
            $__visibleItems = array_filter($section['items'], function ($item) {
                if (! Route::has($item['route'])) {
                    return false;
                }

                return empty($item['permission']) || auth()->user()->can($item['permission']);
            });
        @endphp

        @continue(empty($__visibleItems))

        <li class="nav-header">{{ $section['label'] }}</li>
        @foreach ($__visibleItems as $item)
            @php
                $isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                $badge = null;

                if (isset($item['badge']) && $item['badge'] === 'unread_notifications' && $__unreadCount > 0) {
                    $badge = $__unreadCount;
                } elseif (isset($item['badge']) && $item['badge'] !== 'unread_notifications') {
                    $badge = $item['badge'];
                }
            @endphp
            <li class="nav-item">
                <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                    <span class="tracker-nav-icon-wrap">
                        <i class="nav-icon {{ $item['icon'] }}"></i>
                    </span>
                    <div class="tracker-nav-copy">
                        <p>{{ $item['title'] }}</p>
                        @if (! empty($item['description']))
                            <span>{{ $item['description'] }}</span>
                        @endif
                    </div>
                    @if ($badge)
                        <small class="tracker-nav-badge">{{ $badge }}</small>
                    @endif
                </a>
            </li>
        @endforeach
    @endforeach
</ul>
