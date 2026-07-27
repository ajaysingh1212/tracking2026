@extends('layouts.app')

@section('page-eyebrow', 'Workspace')
@section('page-title', 'My Licenses')

@section('content')
    @if ($activeLicense)
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="tracker-stat-card">
                    <div class="tracker-stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-id-badge"></i></div>
                    <div class="tracker-stat-value">{{ $activeLicense->plan?->name }}</div>
                    <div class="tracker-stat-label">Current Plan</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="tracker-stat-card">
                    <div class="tracker-stat-icon bg-success-subtle text-success"><i class="fa-solid fa-link"></i></div>
                    <div class="tracker-stat-value">{{ $activeLicense->remaining_slots }}</div>
                    <div class="tracker-stat-label">Remaining Slots</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="tracker-stat-card">
                    <div class="tracker-stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-chart-pie"></i></div>
                    <div class="tracker-stat-value">{{ $activeLicense->consumed_slots }}</div>
                    <div class="tracker-stat-label">Consumed Slots</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="tracker-stat-card">
                    <div class="tracker-stat-icon bg-info-subtle text-info"><i class="fa-solid fa-calendar"></i></div>
                    <div class="tracker-stat-value">{{ $activeLicense->expiry_date?->format('d M Y') ?? 'Lifetime' }}</div>
                    <div class="tracker-stat-label">Expiry Date</div>
                </div>
            </div>
        </div>
    @endif

    <div class="card tracker-surface-card">
        <div class="card-header border-0 bg-transparent">
            <h3 class="tracker-card-title mb-1">License History</h3>
            <p class="tracker-card-subtitle mb-0">All plans assigned to your account</p>
        </div>
        <div class="card-body table-responsive">
            <table class="table tracker-table align-middle">
                <thead>
                    <tr><th>License #</th><th>Plan</th><th>Purchased</th><th>Expiry</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($licenses as $license)
                        <tr>
                            <td>{{ $license->license_number }}</td>
                            <td>{{ $license->plan?->name }}</td>
                            <td>{{ $license->purchase_date?->format('d M Y') }}</td>
                            <td>{{ $license->expiry_date?->format('d M Y') ?? 'Lifetime' }}</td>
                            <td>@include('admin.partials.status-pill', ['status' => $license->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="tracker-empty-state"><i class="fa-solid fa-id-card"></i><p class="mb-0">No licenses assigned to your account yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($licenses->hasPages())
            <div class="card-footer border-0 bg-transparent">{{ $licenses->links() }}</div>
        @endif
    </div>
@endsection
