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
                        <div class="tracker-map-person-row {{ $person['isSelf'] ? 'tracker-map-person-row-self' : '' }}" data-person-row="{{ $person['id'] }}" role="button">
                            <div class="tracker-avatar-sm">{{ strtoupper(substr($person['name'], 0, 1)) }}</div>
                            <div class="tracker-map-person-copy">
                                <strong>{{ $person['name'] }}</strong>
                                @if ($person['isSelf'])
                                    <span class="tracker-status-pill tracker-status-info">You</span>
                                @endif
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
                                @if ($person['isSelf'])
                                    <span class="text-muted small d-block">
                                        @if (count($person['trackedBy']))
                                            Tracked by: {{ implode(', ', $person['trackedBy']) }}
                                        @else
                                            Not tracked by anyone right now
                                        @endif
                                    </span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm tracker-outline-btn tracker-live-report-btn" data-report-user="{{ $person['id'] }}" title="Open reports">
                                <i class="fa-solid fa-chart-pie"></i>
                            </button>
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

    <div class="modal fade" id="live-map-report-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content tracker-modal">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title" id="live-map-report-title">Employee Reports</h5>
                        <p class="tracker-card-subtitle mb-0">Realtime Phase 4 monitoring reports</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="tracker-form-label">Report</label>
                            <select class="form-select" id="live-report-type">
                                @foreach (['employee_daily', 'employee_weekly', 'employee_monthly', 'attendance', 'travel', 'mileage', 'idle', 'geofence', 'visit', 'diagnostic', 'communication', 'license_usage', 'organization_summary'] as $type)
                                    <option value="{{ $type }}">{{ Str::headline($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="tracker-form-label">Range</label>
                            <select class="form-select" id="live-report-preset">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="last_7_days">Last 7 Days</option>
                                <option value="last_30_days">Last 30 Days</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="tracker-form-label">Min speed</label>
                            <input type="number" min="0" step="0.1" class="form-control" id="live-report-min-speed">
                        </div>
                        <div class="col-md-2">
                            <label class="tracker-form-label">Max speed</label>
                            <input type="number" min="0" step="0.1" class="form-control" id="live-report-max-speed">
                        </div>
                        <div class="col-md-1 d-grid">
                            <label class="tracker-form-label">&nbsp;</label>
                            <button type="button" class="btn tracker-primary-btn" id="live-report-load"><i class="fa-solid fa-rotate"></i></button>
                        </div>
                    </div>
                    <div id="live-report-chart-root"></div>
                    <div id="live-report-content-block" class="d-none">
                        <div class="row g-3 mb-3" id="live-report-cards"></div>
                        <div class="row g-3 mb-3" id="live-report-menu"></div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <h6 class="tracker-card-title">Device Diagnostics — GPS / Internet / Battery History</h6>
                                <div class="tracker-report-table-wrap">
                                    <table class="table table-sm tracker-report-table">
                                        <thead><tr><th>Event</th><th>Time</th><th>Battery</th><th>Network</th></tr></thead>
                                        <tbody id="live-report-diagnostics"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h6 class="tracker-card-title">Geofence Activity</h6>
                                <div class="tracker-report-table-wrap">
                                    <table class="table table-sm tracker-report-table">
                                        <thead><tr><th>Geofence</th><th>Type</th><th>Time</th></tr></thead>
                                        <tbody id="live-report-geofence"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <pre class="mt-3 mb-0 tracker-report-json" id="live-report-output">Loading...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
