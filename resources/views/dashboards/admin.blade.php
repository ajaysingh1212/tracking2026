<x-app-layout>
    <x-slot name="header">Admin Dashboard</x-slot>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner"><h3>{{ $stats['totalUsers'] }}</h3><p>Total Users</p></div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner"><h3>{{ $stats['activeUsers'] }}</h3><p>Active Users</p></div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner"><h3>{{ $stats['licenses'] }}</h3><p>Total Licenses</p></div>
                <div class="icon"><i class="fas fa-id-card"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner"><h3>{{ $stats['expiredLicenses'] }}</h3><p>Expired Licenses</p></div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Latest Activities</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-striped" data-datatable>
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>User</th>
                                <th>IP</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['latestActivities'] as $activity)
                                <tr>
                                    <td>{{ $activity->event }}</td>
                                    <td>{{ $activity->user?->name ?? 'System' }}</td>
                                    <td>{{ $activity->ip_address }}</td>
                                    <td>{{ $activity->logged_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Charts</h3></div>
                <div class="card-body">
                    <div class="skeleton mb-3"></div>
                    <p class="text-muted mb-0">Chart widgets are wired for future Phase 2 data feeds.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-7">Managers</dt><dd class="col-sm-5 text-end">{{ $stats['managers'] }}</dd>
                        <dt class="col-sm-7">Admins</dt><dd class="col-sm-5 text-end">{{ $stats['admins'] }}</dd>
                        <dt class="col-sm-7">Active Licenses</dt><dd class="col-sm-5 text-end">{{ $stats['activeLicenses'] }}</dd>
                        <dt class="col-sm-7">Today's Logins</dt><dd class="col-sm-5 text-end">{{ $stats['todaysLogins'] }}</dd>
                        <dt class="col-sm-7">Today's Registrations</dt><dd class="col-sm-5 text-end">{{ $stats['todaysRegistrations'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
