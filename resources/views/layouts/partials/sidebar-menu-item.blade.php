@php
    $isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
    $isChild = $child ?? false;
    $badge = null;

    if (isset($item['badge']) && $item['badge'] === 'unread_notifications' && ($__unreadCount ?? 0) > 0) {
        $badge = $__unreadCount;
    } elseif (isset($item['badge']) && $item['badge'] !== 'unread_notifications') {
        $badge = $item['badge'];
    }
@endphp
<li class="nav-item">
    <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }} {{ $isChild ? 'tracker-nav-link-sub' : '' }}">
        <span class="tracker-nav-icon-wrap {{ $isChild ? 'tracker-nav-icon-wrap-sm' : '' }}">
            <i class="nav-icon {{ $item['icon'] }}"></i>
        </span>
        <div class="tracker-nav-copy">
            <p>{{ $item['title'] }}</p>
            @if (! empty($item['description']) && ! $isChild)
                <span>{{ $item['description'] }}</span>
            @endif
        </div>
        @if ($badge)
            <small class="tracker-nav-badge nav-badge">{{ $badge }}</small>
        @endif
    </a>
</li>
