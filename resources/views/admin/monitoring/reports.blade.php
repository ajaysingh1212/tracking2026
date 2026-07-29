@extends('layouts.app')

@section('page-eyebrow', 'Phase 4')
@section('page-title', 'Reports & Export Center')

@section('content')
    <div class="card tracker-surface-card">
        <div class="card-header border-0 bg-transparent">
            <h3 class="tracker-card-title mb-1">Generate a report</h3>
            <p class="tracker-card-subtitle mb-0">{{ $reportUsers->count() }} trackable people available to you</p>
        </div>
        <div class="card-body pt-0">
            @if ($reportUsers->isEmpty())
                <div class="tracker-empty-state">
                    <i class="fa-solid fa-file-export"></i>
                    <p class="mb-0">No tracked, licensed people are available for reports yet.</p>
                </div>
            @else
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="tracker-form-label">Person</label>
                        <select class="form-select" id="report-user">
                            @foreach ($reportUsers as $reportUser)
                                <option value="{{ $reportUser->id }}">{{ $reportUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="tracker-form-label">Report</label>
                        <select class="form-select" id="report-type">
                            @foreach (['employee_daily', 'employee_weekly', 'employee_monthly', 'attendance', 'travel', 'mileage', 'idle', 'geofence', 'visit', 'diagnostic', 'communication', 'license_usage', 'organization_summary'] as $type)
                                <option value="{{ $type }}">{{ Str::headline($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="tracker-form-label">Range</label>
                        <select class="form-select" id="report-preset">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="last_7_days">Last 7 Days</option>
                            <option value="last_30_days">Last 30 Days</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-none" id="report-custom-range">
                        <label class="tracker-form-label">From / To</label>
                        <div class="d-flex gap-1">
                            <input type="date" class="form-control" id="report-from">
                            <input type="date" class="form-control" id="report-to">
                        </div>
                    </div>
                    <div class="col-md-2 d-grid">
                        <label class="tracker-form-label">&nbsp;</label>
                        <button type="button" class="btn tracker-primary-btn" id="report-generate">
                            <i class="fa-solid fa-bolt"></i> Generate
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="tracker-form-label">Min speed</label>
                        <input type="number" min="0" step="0.1" class="form-control" id="report-min-speed">
                    </div>
                    <div class="col-md-2">
                        <label class="tracker-form-label">Max speed</label>
                        <input type="number" min="0" step="0.1" class="form-control" id="report-max-speed">
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="report-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content tracker-modal">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title" id="report-modal-title">Report</h5>
                        <p class="tracker-card-subtitle mb-0">
                            <span class="tracker-status-pill tracker-status-active" id="report-live-pill">Live</span>
                            Auto-refreshes every 30s while open
                        </p>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <a class="btn btn-sm tracker-outline-btn" id="report-export-csv" href="#" target="_blank">CSV</a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3" id="report-cards"></div>
                    <div class="row g-3 mb-3" id="report-menu"></div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="tracker-card-title">Device Diagnostics — GPS / Internet / Battery History</h6>
                            <div class="tracker-report-table-wrap">
                                <table class="table table-sm tracker-report-table">
                                    <thead><tr><th>Event</th><th>Time</th><th>Battery</th><th>Network</th></tr></thead>
                                    <tbody id="report-diagnostics"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="tracker-card-title">Geofence Activity</h6>
                            <div class="tracker-report-table-wrap">
                                <table class="table table-sm tracker-report-table">
                                    <thead><tr><th>Geofence</th><th>Type</th><th>Time</th></tr></thead>
                                    <tbody id="report-geofence"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <pre class="mt-3 mb-0 tracker-report-json" id="report-output">Loading...</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modalEl = document.getElementById('report-modal');
            if (!modalEl || document.getElementById('report-user') === null) return;

            const modal = new bootstrap.Modal(modalEl);
            let refreshTimer = null;

            document.getElementById('report-preset').addEventListener('change', (event) => {
                document.getElementById('report-custom-range').classList.toggle('d-none', event.target.value !== 'custom');
            });

            function currentParams() {
                const params = {
                    user_id: document.getElementById('report-user').value,
                    type: document.getElementById('report-type').value,
                    preset: document.getElementById('report-preset').value,
                };

                if (params.preset === 'custom') {
                    params.from = document.getElementById('report-from').value;
                    params.to = document.getElementById('report-to').value;
                }

                const minSpeed = document.getElementById('report-min-speed').value;
                const maxSpeed = document.getElementById('report-max-speed').value;
                if (minSpeed !== '') params.min_speed = minSpeed;
                if (maxSpeed !== '') params.max_speed = maxSpeed;

                return params;
            }

            function renderCards(analytics, attendance, timeline, heatmap, report) {
                const internetOutages = report.device_diagnostics?.internet_outages ?? [];
                const gpsOutages = report.device_diagnostics?.gps_outages ?? [];
                const internetDownMinutes = Math.round(internetOutages.reduce((sum, o) => sum + o.duration_seconds, 0) / 60);

                const cards = [
                    ['Distance', `${analytics.total_distance_km ?? 0} km`],
                    ['Moving', `${Math.round((analytics.moving_seconds ?? 0) / 60)} min`],
                    ['Idle', `${Math.round((analytics.idle_seconds ?? 0) / 60)} min`],
                    ['Attendance', attendance.status ?? '—'],
                    ['Work Hours', `${attendance.working_hours ?? 0} h`],
                    ['Timeline', `${timeline.length} events`],
                    ['Heat Zones', `${heatmap.summary?.clusters ?? 0}`],
                    ['Max Speed', `${analytics.maximum_speed_mps ?? '—'} m/s`],
                    ['Internet Outages', `${internetOutages.length} (${internetDownMinutes} min down)`],
                    ['GPS Outages', `${gpsOutages.length}`],
                    ['Geofence Events', `${report.geofence_activity?.length ?? 0}`],
                ];

                document.getElementById('report-cards').innerHTML = cards.map(([label, value]) => `
                    <div class="col-md-3">
                        <div class="card tracker-surface-card h-100">
                            <div class="card-body">
                                <p class="tracker-card-subtitle mb-1">${label}</p>
                                <h4 class="tracker-card-title mb-0">${value}</h4>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            function renderDiagnostics(deviceDiagnostics) {
                const rows = deviceDiagnostics?.events ?? [];
                const tbody = document.getElementById('report-diagnostics');

                tbody.innerHTML = rows.length
                    ? rows.map((event) => `
                        <tr>
                            <td>${event.label}</td>
                            <td>${new Date(event.occurred_at).toLocaleString()}</td>
                            <td>${event.battery_level !== null && event.battery_level !== undefined ? event.battery_level + '%' : '—'}</td>
                            <td>${event.network_type || '—'}</td>
                        </tr>
                    `).join('')
                    : '<tr><td colspan="4" class="text-muted">No diagnostic events in this range.</td></tr>';
            }

            function renderGeofenceActivity(activity) {
                const tbody = document.getElementById('report-geofence');

                tbody.innerHTML = (activity ?? []).length
                    ? activity.map((event) => `
                        <tr>
                            <td>${event.geofence_name ?? '—'}</td>
                            <td>${event.type}</td>
                            <td>${new Date(event.occurred_at).toLocaleString()}</td>
                        </tr>
                    `).join('')
                    : '<tr><td colspan="3" class="text-muted">No geofence activity in this range.</td></tr>';
            }

            function renderMenu(tables) {
                document.getElementById('report-menu').innerHTML = (tables ?? []).map((table) => `
                    <div class="col-md-6 col-xl-4">
                        <div class="card tracker-surface-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong>${table.label}</strong>
                                    <span class="tracker-status-pill tracker-status-active">${table.records}</span>
                                </div>
                                <p class="tracker-card-subtitle mb-1">${table.description}</p>
                                <div class="small text-muted">Formats: ${table.formats.join(', ')}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            async function loadReport() {
                const params = currentParams();
                const output = document.getElementById('report-output');
                output.textContent = 'Loading realtime report...';

                let report; let analytics; let attendance; let timeline; let heatmap;

                try {
                    [{ data: report }, { data: analytics }, { data: attendance }, { data: timeline }, { data: heatmap }] = await Promise.all([
                        axios.get('/api/v1/reports', { params }),
                        axios.get('/api/v1/analytics', { params }),
                        axios.get('/api/v1/attendance', { params }),
                        axios.get('/api/v1/timeline', { params }),
                        axios.get('/api/v1/heatmap', { params }),
                    ]);
                } catch (error) {
                    output.textContent = `Could not load report: ${error.response?.data?.message ?? error.message}`;

                    return;
                }

                renderCards(analytics.data, attendance.data, timeline.data, heatmap.data, report.data);
                renderMenu(report.data.phase4_tables);
                renderDiagnostics(report.data.device_diagnostics);
                renderGeofenceActivity(report.data.geofence_activity);

                output.textContent = JSON.stringify({
                    report: report.data,
                    analytics: analytics.data,
                    attendance: attendance.data,
                    timeline: timeline.data.slice(0, 25),
                    heatmap: heatmap.data.summary,
                }, null, 2);

                const personName = document.getElementById('report-user').selectedOptions[0]?.textContent ?? 'Employee';
                document.getElementById('report-modal-title').textContent = `${personName} — ${document.getElementById('report-type').selectedOptions[0]?.textContent}`;
                document.getElementById('report-export-csv').href = `/api/v1/exports?format=csv&${new URLSearchParams(params).toString()}`;
            }

            document.getElementById('report-generate').addEventListener('click', () => {
                loadReport();
                modal.show();

                clearInterval(refreshTimer);
                refreshTimer = setInterval(loadReport, 30000);
            });

            modalEl.addEventListener('hidden.bs.modal', () => clearInterval(refreshTimer));
        })();
    </script>
@endpush
