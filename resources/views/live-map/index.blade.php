@extends('layouts.app')

@section('page-eyebrow', 'Realtime')
@section('page-title', 'Live Map')

@push('scripts')
    @vite(['resources/js/live-map.js'])
@endpush

@section('content')
    <script id="live-map-data" type="application/json">{!! json_encode($people) !!}</script>

    <div class="row g-4">
        <div class="col-xl-9">
            <div class="card tracker-surface-card">
                <div class="card-body p-0">
                    <div id="live-map" class="tracker-live-map"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card tracker-surface-card">
                <div class="card-header border-0 bg-transparent">
                    <h3 class="tracker-card-title mb-1">Tracked People</h3>
                    <p class="tracker-card-subtitle mb-0">{{ $people->count() }} visible</p>
                </div>
                <div class="card-body pt-0">
                    @forelse ($people as $person)
                        <div class="tracker-map-person-row" data-person-row="{{ $person['id'] }}" role="button">
                            <div class="tracker-avatar-sm">{{ strtoupper(substr($person['name'], 0, 1)) }}</div>
                            <div class="tracker-map-person-copy">
                                <strong>{{ $person['name'] }}</strong>
                                <span data-field="status" class="tracker-status-pill {{ $person['isOnline'] ? 'tracker-status-active' : 'tracker-status-muted' }}">
                                    {{ $person['isOnline'] ? 'Online' : 'Offline' }}
                                </span>
                                <span data-field="movement" class="text-muted small {{ $person['isOnline'] && $person['lat'] !== null ? '' : 'd-none' }}">
                                    {{ $person['movementStatus'] === 'moving' ? 'Moving' : 'Idle' }}
                                    · {{ $person['speed'] !== null ? number_format($person['speed'] * 3.6, 1).' km/h' : '—' }}
                                </span>
                                <span data-field="last-seen" class="text-muted small">
                                    @if ($person['lat'] !== null)
                                        {{ $person['lastSeen'] ? \Illuminate\Support\Carbon::parse($person['lastSeen'])->diffForHumans() : 'Never' }}
                                    @elseif ($person['isOnline'])
                                        No location shared yet
                                    @else
                                        Never logged in
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="tracker-empty-state">
                            <i class="fa-solid fa-map-location-dot"></i>
                            <p class="mb-0">No tracked people yet. Set up a tracking relation to see them here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
