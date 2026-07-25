<x-app-layout>
    <x-slot name="header">User Dashboard</x-slot>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner"><h3>{{ $activeLicense?->plan?->name ?? 'N/A' }}</h3><p>Current License</p></div>
                <div class="icon"><i class="fas fa-id-badge"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner"><h3>{{ $activeLicense?->remaining_slots ?? 0 }}</h3><p>Remaining Slots</p></div>
                <div class="icon"><i class="fas fa-link"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner"><h3>{{ $activeLicense?->consumed_slots ?? 0 }}</h3><p>Consumed Slots</p></div>
                <div class="icon"><i class="fas fa-chart-pie"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner"><h3>{{ auth()->user()->status->label() }}</h3><p>Account Status</p></div>
                <div class="icon"><i class="fas fa-shield-halved"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Recent Notifications</h3></div>
                <div class="card-body">
                    @forelse ($stats['notifications'] as $notification)
                        <div class="alert alert-light border mb-2">{{ $notification->data['message'] ?? 'Notification' }}</div>
                    @empty
                        <div class="alert alert-light border mb-0">No recent notifications.</div>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Login History</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-striped" data-datatable>
                        <thead><tr><th>Email</th><th>IP</th><th>Attempted At</th></tr></thead>
                        <tbody>
                            @foreach ($stats['loginHistory'] as $entry)
                                <tr>
                                    <td>{{ $entry->email }}</td>
                                    <td>{{ $entry->ip_address }}</td>
                                    <td>{{ $entry->attempted_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Update Profile</a>
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#supportModal">Contact Support</button>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5>Support</h5>
                    <p class="mb-1">Tickets: {{ $stats['supportTickets'] }}</p>
                    <p class="mb-0 text-muted">Tracking widget architecture is ready for next phase.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="supportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Support</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Support workflow is available through the support module foundation.</div>
            </div>
        </div>
    </div>
</x-app-layout>
