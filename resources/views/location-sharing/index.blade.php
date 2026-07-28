@extends('layouts.app')

@section('page-eyebrow', 'Realtime')
@section('page-title', 'Share My Location')

@push('scripts')
    @vite(['resources/js/gps-watcher.js'])
@endpush

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card tracker-surface-card">
                <div class="card-header border-0 bg-transparent">
                    <h3 class="tracker-card-title mb-1">Browser Location Sharing</h3>
                    <p class="tracker-card-subtitle mb-0">
                        Turn this on to broadcast this browser tab's position to anyone tracking you, exactly
                        like the mobile app would.
                    </p>
                </div>
                <div class="card-body">
                    <button type="button" class="btn tracker-primary-btn" id="location-sharing-toggle">
                        <i class="fa-solid fa-location-crosshairs me-2"></i>
                        <span id="location-sharing-toggle-label">Start Sharing</span>
                    </button>

                    <div class="tracker-mini-list mt-4">
                        <div class="tracker-mini-item">
                            <span>Status</span>
                            <strong id="location-sharing-status">Stopped</strong>
                        </div>
                        <div class="tracker-mini-item">
                            <span>Accuracy</span>
                            <strong id="location-sharing-accuracy">—</strong>
                        </div>
                        <div class="tracker-mini-item">
                            <span>Last Sent</span>
                            <strong id="location-sharing-last-sent">Never</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card tracker-surface-card">
                <div class="card-header border-0 bg-transparent">
                    <h3 class="tracker-card-title mb-1">How it works</h3>
                </div>
                <div class="card-body">
                    <div class="tracker-notice-item">
                        <div class="tracker-notice-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <div>Your browser will ask for location permission the first time you start sharing.</div>
                    </div>
                    <div class="tracker-notice-item">
                        <div class="tracker-notice-icon"><i class="fa-solid fa-wifi"></i></div>
                        <div>If you go offline, points are queued on this device and sent automatically once you're back online.</div>
                    </div>
                    <div class="tracker-notice-item">
                        <div class="tracker-notice-icon"><i class="fa-solid fa-gauge-high"></i></div>
                        <div>Update frequency adapts to your speed — fewer updates while standing still, more while moving.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
