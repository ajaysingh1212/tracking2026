@extends('layouts.app')

@section('page-eyebrow', 'Workspace')
@section('page-title', 'Notifications')

@section('page-actions')
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn tracker-outline-btn"><i class="fa-solid fa-check-double me-2"></i>Mark All Read</button>
    </form>
@endsection

@section('content')
    <div class="card tracker-surface-card">
        <div class="card-body">
            @forelse ($notifications as $notification)
                <div class="tracker-notice-item">
                    <div class="tracker-notice-icon"><i class="fa-regular fa-bell"></i></div>
                    <div class="flex-fill">
                        <div>{{ $notification->data['message'] ?? 'Notification' }}</div>
                        <span class="small text-muted">{{ $notification->created_at?->diffForHumans() }}</span>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm tracker-outline-btn">Mark Read</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="tracker-empty-state"><i class="fa-regular fa-bell"></i><p class="mb-0">No notifications yet.</p></div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer border-0 bg-transparent">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
