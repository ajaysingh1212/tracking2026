import './bootstrap';
import { Modal } from 'bootstrap';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { mountReportCharts } from './report-charts';

const LIGHT_TILES = {
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
};

const DARK_TILES = {
    // Use the same key-free provider in dark mode. The dark appearance is
    // applied with CSS so the map does not depend on a paid tile API key.
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
};

const DEFAULT_CENTER = [20.5937, 78.9629];
const TWEEN_MS = 1000;

function markerIcon(initial, isOnline, isSelf = false) {
    const classes = ['tracker-map-marker', isOnline ? '' : 'tracker-map-marker-offline', isSelf ? 'tracker-map-marker-self' : ''];

    return L.divIcon({
        className: classes.filter(Boolean).join(' '),
        html: `<span class="tracker-map-marker-arrow"></span><span class="tracker-map-marker-dot">${initial}</span>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });
}

function formatLastSeen(iso) {
    if (!iso) return 'Never';

    const diffSeconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000);

    if (diffSeconds < 60) return `${diffSeconds}s ago`;
    if (diffSeconds < 3600) return `${Math.round(diffSeconds / 60)}m ago`;

    return `${Math.round(diffSeconds / 3600)}h ago`;
}

function formatSpeed(speedMps) {
    if (speedMps === null || speedMps === undefined) return '—';

    const kmh = speedMps * 3.6;

    return `${kmh.toFixed(1)} km/h`;
}

function movementLabel(status) {
    return status === 'moving' ? 'Moving' : 'Idle';
}

function complianceClass(status) {
    if (status === 'visited') return 'tracker-status-active';
    if (status === 'missed') return 'tracker-status-danger';

    return 'tracker-status-pending';
}

function distanceMeters(lat1, lng1, lat2, lng2) {
    const radius = 6371000;
    const toRadians = (value) => value * Math.PI / 180;
    const latDelta = toRadians(lat2 - lat1);
    const lngDelta = toRadians(lng2 - lng1);
    const a = Math.sin(latDelta / 2) ** 2
        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(lngDelta / 2) ** 2;

    return radius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function geofenceContains(geofence, lat, lng) {
    if (geofence.type === 'circle') {
        return distanceMeters(lat, lng, Number(geofence.center_lat), Number(geofence.center_lng)) <= Number(geofence.radius_meters);
    }

    const points = geofence.points ?? [];
    if (points.length < 3) return true;

    let inside = false;
    for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
        const yi = Number(points[i].latitude);
        const xi = Number(points[i].longitude);
        const yj = Number(points[j].latitude);
        const xj = Number(points[j].longitude);
        const intersects = ((yi > lat) !== (yj > lat))
            && (lng < ((xj - xi) * (lat - yi)) / ((yj - yi) || Number.EPSILON) + xi);
        if (intersects) inside = !inside;
    }

    return inside;
}

class LiveMap {
    constructor(containerId, people) {
        this.people = new Map(people.map((person) => [person.id, person]));
        this.markers = new Map();
        this.reportModalEl = document.getElementById('live-map-report-modal');
        this.reportModal = this.reportModalEl ? new Modal(this.reportModalEl) : null;
        this.reportUserId = null;
        this.reportCharts = null;

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const tiles = isDark ? DARK_TILES : LIGHT_TILES;

        document.getElementById(containerId)?.classList.toggle('tracker-live-map-dark', isDark);

        this.map = L.map(containerId, { zoomControl: true }).setView(DEFAULT_CENTER, 5);
        L.tileLayer(tiles.url, { attribution: tiles.attribution, maxZoom: 19 }).addTo(this.map);

        this.geofenceLayer = L.layerGroup().addTo(this.map);

        const withLocation = people.filter((p) => p.lat !== null && p.lng !== null);

        withLocation.forEach((person) => this._placeMarker(person));

        if (withLocation.length > 0) {
            const bounds = L.latLngBounds(withLocation.map((p) => [p.lat, p.lng]));
            this.map.fitBounds(bounds.pad(0.3));
        }

        people.forEach((person) => this._subscribe(person));
        people.forEach((person) => this._updateGeofenceState(person));
        this._renderGeofenceAlerts();
        this._bindReportModal();
    }

    _placeMarker(person) {
        const marker = L.marker([person.lat, person.lng], {
            icon: markerIcon(person.name.charAt(0).toUpperCase(), person.isOnline, person.isSelf),
        }).addTo(this.map);

        marker.bindPopup(this._popupHtml(person));
        marker.on('click', () => this._showGeofencesFor(person));
        this.markers.set(person.id, marker);
    }

    _showGeofencesFor(person) {
        this.geofenceLayer.clearLayers();

        const geofences = person.geofences ?? [];

        if (!geofences.length) return;

        const shapes = [];

        geofences.forEach((geofence) => {
            let shape;

            if (geofence.type === 'circle') {
                shape = L.circle([geofence.center_lat, geofence.center_lng], {
                    radius: geofence.radius_meters,
                    color: geofence.color,
                    fillOpacity: 0.12,
                });
            } else if (geofence.points?.length >= 3) {
                shape = L.polygon(geofence.points.map((point) => [point.latitude, point.longitude]), {
                    color: geofence.color,
                    fillOpacity: 0.12,
                });
            }

            if (!shape) return;

            const label = [
                geofence.sequence ? `#${geofence.sequence}` : null,
                geofence.name,
                geofence.route_label ? `Route: ${geofence.route_label}` : null,
                `Status: ${geofence.compliance_status}`,
            ].filter(Boolean).join(' | ');

            shape.bindTooltip(label, { sticky: true });
            shape.bindPopup(`
                <div class="tracker-map-popup">
                    <strong>${geofence.name}</strong>
                    <div class="tracker-map-popup-badges">
                        <span class="tracker-status-pill ${complianceClass(geofence.compliance_status)}">${geofence.compliance_status}</span>
                    </div>
                    <div>Route: ${geofence.route_label || 'Default'}</div>
                    <div>Order: ${geofence.sequence ?? '-'}</div>
                    <div>Routine: ${geofence.schedule_type}</div>
                    <div>Window: ${geofence.window_start || '-'} to ${geofence.window_end || '-'}</div>
                    <div>${geofence.compliance_status === 'missed' ? 'Pending/bypassed checkpoint: user did not visit this location in the expected window.' : ''}</div>
                </div>
            `);
            shape.addTo(this.geofenceLayer);
            shapes.push(shape);
        });

        if (shapes.length) {
            const bounds = shapes.reduce((acc, shape) => (acc ? acc.extend(shape.getBounds()) : L.latLngBounds(shape.getBounds().getSouthWest(), shape.getBounds().getNorthEast())), null);
            this.map.fitBounds(bounds.pad(0.3));
        }
    }

    _popupHtml(person) {
        const statusClass = person.isOnline ? 'tracker-status-active' : 'tracker-status-muted';
        const statusLabel = person.isOnline ? 'Online' : 'Offline';

        return `
            <div class="tracker-map-popup">
                <strong>${person.name}${person.isSelf ? ' (You)' : ''}</strong>
                <div class="tracker-map-popup-badges">
                    <span class="tracker-status-pill ${statusClass}">${statusLabel}</span>
                    ${person.isOnline ? `<span class="tracker-status-pill ${person.movementStatus === 'moving' ? 'tracker-status-active' : 'tracker-status-pending'}">${movementLabel(person.movementStatus)}</span>` : ''}
                </div>
                <div>Speed: ${formatSpeed(person.speed)}</div>
                <div>Battery: ${person.battery !== null && person.battery !== undefined ? person.battery + '%' : '—'}</div>
                <div>GPS: ${person.gpsEnabled === null || person.gpsEnabled === undefined ? '—' : (person.gpsEnabled ? 'On' : 'Off')}</div>
                <div>Internet: ${person.internetEnabled === null || person.internetEnabled === undefined ? '—' : (person.internetEnabled ? 'On' : 'Off')}</div>
                <div>Network: ${person.networkType || '—'}</div>
                <div>Last location: ${formatLastSeen(person.lastSeen)}</div>
                <div>Assigned geofences: ${(person.geofences ?? []).length}</div>
                ${person.isSelf ? `<div>Tracked by: ${person.trackedBy?.length ? person.trackedBy.join(', ') : 'No one right now'}</div>` : ''}
            </div>
        `;
    }

    _subscribe(person) {
        const channel = window.Echo.private(`App.Models.User.${person.id}`);

        channel.listen('.gps.location.updated', (payload) => this._onLocationUpdate(person.id, payload));
        channel.listen('.presence.changed', (payload) => this._onPresenceChange(person.id, payload));
        channel.listen('.geofence.event', (payload) => this._onGeofenceEvent(person, payload));
        channel.listen('.geofence.overspeed', (payload) => this._onGeofenceOverspeed(person, payload));
    }

    _toast(icon, title) {
        window.Swal?.fire({
            toast: true,
            position: 'top-end',
            timer: 6000,
            timerProgressBar: true,
            showConfirmButton: false,
            icon,
            title,
        });
    }

    _onGeofenceEvent(person, payload) {
        const who = person.isSelf ? 'You' : person.name;
        const verb = payload.type === 'entered' ? 'entered' : 'exited';

        this._toast('info', `${who} ${verb} ${payload.geofence.name}`);

        const geofence = (person.geofences ?? []).find((item) => item.uuid === payload.geofence.uuid);
        if (geofence) geofence.is_outside = payload.type === 'exited';
        this._applyGeofenceWarning(person);
        this._renderGeofenceAlerts();
    }

    _onGeofenceOverspeed(person, payload) {
        const who = person.isSelf ? 'You are' : `${person.name} is`;

        this._toast('warning', `${who} overspeeding in ${payload.geofence.name}: ${payload.speed_kmh} km/h (limit ${payload.limit_kmh} km/h)`);
    }

    _onLocationUpdate(personId, payload) {
        const person = this.people.get(personId);

        if (!person) return;

        const from = { lat: person.lat ?? payload.latitude, lng: person.lng ?? payload.longitude };
        const to = { lat: payload.latitude, lng: payload.longitude };

        Object.assign(person, {
            lat: payload.latitude,
            lng: payload.longitude,
            speed: payload.speed,
            bearing: payload.bearing,
            battery: payload.battery_level,
            gpsEnabled: payload.is_gps_enabled ?? person.gpsEnabled,
            internetEnabled: payload.is_internet_enabled ?? person.internetEnabled,
            networkType: payload.network_type ?? person.networkType,
            movementStatus: payload.movement_status,
            isOnline: true,
            lastSeen: payload.recorded_at,
        });

        this._updateGeofenceState(person);

        let marker = this.markers.get(personId);

        if (!marker) {
            marker = L.marker([to.lat, to.lng], { icon: markerIcon(person.name.charAt(0).toUpperCase(), true, person.isSelf) }).addTo(this.map);
            marker.bindPopup(this._popupHtml(person));
            marker.on('click', () => this._showGeofencesFor(person));
            this.markers.set(personId, marker);
        } else {
            this._tween(marker, from, to);
            marker.setPopupContent(this._popupHtml(person));
            this._setMarkerOnline(marker, true);
        }

        if (payload.bearing !== null) {
            const arrow = marker.getElement()?.querySelector('.tracker-map-marker-arrow');
            if (arrow) arrow.style.transform = `rotate(${payload.bearing}deg)`;
        }

        this._updateListRow(personId, person);
        this._renderGeofenceAlerts();
    }

    _updateGeofenceState(person) {
        if (person.lat === null || person.lat === undefined || person.lng === null || person.lng === undefined) return;

        (person.geofences ?? []).forEach((geofence) => {
            geofence.is_outside = !geofenceContains(geofence, Number(person.lat), Number(person.lng));
        });
        this._applyGeofenceWarning(person);
    }

    _applyGeofenceWarning(person) {
        const outside = (person.geofences ?? []).some((geofence) => geofence.is_outside);
        const row = document.querySelector(`[data-person-row="${person.id}"]`);
        row?.classList.toggle('tracker-map-person-row-warning', outside);
        row?.querySelector('[data-field="geofence-warning"]')?.classList.toggle('d-none', !outside);
        this.markers.get(person.id)?.getElement()?.classList.toggle('tracker-map-marker-warning', outside);
    }

    _renderGeofenceAlerts() {
        const root = document.getElementById('live-map-geofence-alerts');
        if (!root) return;

        const alerts = [];
        this.people.forEach((person) => {
            const outside = (person.geofences ?? []).filter((geofence) => geofence.is_outside);
            if (outside.length) alerts.push({ person, outside });
        });

        root.classList.toggle('d-none', alerts.length === 0);
        root.innerHTML = alerts.map(({ person, outside }) => `
            <div class="tracker-live-map-alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><strong>${person.name} is outside the assigned geofence</strong><span>${outside.map((item) => item.name).join(', ')}</span></div>
            </div>
        `).join('');
    }

    _onPresenceChange(personId, payload) {
        const person = this.people.get(personId);

        if (!person) return;

        Object.assign(person, {
            isOnline: payload.is_online,
            lastActivity: payload.last_activity_at,
        });

        const marker = this.markers.get(personId);

        if (marker) {
            this._setMarkerOnline(marker, payload.is_online);
            marker.setPopupContent(this._popupHtml(person));
        }

        this._updateListRow(personId, person);
    }

    _setMarkerOnline(marker, isOnline) {
        const el = marker.getElement();

        el?.classList.toggle('tracker-map-marker-offline', !isOnline);
    }

    _tween(marker, from, to) {
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min(1, (now - start) / TWEEN_MS);
            const lat = from.lat + (to.lat - from.lat) * progress;
            const lng = from.lng + (to.lng - from.lng) * progress;

            marker.setLatLng([lat, lng]);

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    }

    _updateListRow(personId, person) {
        const row = document.querySelector(`[data-person-row="${personId}"]`);

        if (!row) return;

        const statusEl = row.querySelector('[data-field="status"]');

        if (statusEl) {
            statusEl.replaceChildren(document.createTextNode(person.isOnline ? 'Online' : 'Offline'));
            statusEl.classList.toggle('tracker-status-active', person.isOnline);
            statusEl.classList.toggle('tracker-status-muted', !person.isOnline);
        }

        const movementEl = row.querySelector('[data-field="movement"]');

        if (movementEl) {
            if (person.isOnline && person.lat !== null && person.lat !== undefined) {
                movementEl.replaceChildren(document.createTextNode(`${movementLabel(person.movementStatus)} · ${formatSpeed(person.speed)}`));
                movementEl.classList.remove('d-none');
            } else {
                movementEl.classList.add('d-none');
            }
        }

        row.querySelector('[data-field="last-seen"]')?.replaceChildren(
            document.createTextNode(person.lat !== null && person.lat !== undefined ? formatLastSeen(person.lastSeen) : (person.isOnline ? 'No location shared yet' : 'Never logged in')),
        );

        row.addEventListener('click', () => {
            this._showGeofencesFor(person);

            if (person.lat === null || person.lat === undefined) return;

            this.map.flyTo([person.lat, person.lng], 15);
            this.markers.get(personId)?.openPopup();
        });
    }

    _bindReportModal() {
        document.querySelectorAll('[data-report-user]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                this.reportUserId = Number(button.dataset.reportUser);
                const person = this.people.get(this.reportUserId);
                document.getElementById('live-map-report-title').textContent = `${person?.name ?? 'Employee'} Reports`;
                this.reportModal?.show();
                this._loadReport();
            });
        });

        document.getElementById('live-report-load')?.addEventListener('click', () => this._loadReport());
        document.getElementById('live-report-type')?.addEventListener('change', () => this._loadReport());
        document.getElementById('live-report-preset')?.addEventListener('change', () => this._loadReport());
    }

    async _loadReport() {
        if (!this.reportUserId) return;

        const params = {
            user_id: this.reportUserId,
            type: document.getElementById('live-report-type')?.value ?? 'employee_daily',
            preset: document.getElementById('live-report-preset')?.value ?? 'today',
        };

        const minSpeed = document.getElementById('live-report-min-speed')?.value;
        const maxSpeed = document.getElementById('live-report-max-speed')?.value;
        if (minSpeed !== '') params.min_speed = minSpeed;
        if (maxSpeed !== '') params.max_speed = maxSpeed;

        const output = document.getElementById('live-report-output');
        output.textContent = 'Loading realtime report...';

        let report; let analytics; let attendance; let timeline; let heatmap;

        try {
            [{ data: report }, { data: analytics }, { data: attendance }, { data: timeline }, { data: heatmap }] = await Promise.all([
                window.axios.get('/api/v1/reports', { params }),
                window.axios.get('/api/v1/analytics', { params }),
                window.axios.get('/api/v1/attendance', { params }),
                window.axios.get('/api/v1/timeline', { params }),
                window.axios.get('/api/v1/heatmap', { params }),
            ]);
        } catch (error) {
            output.textContent = `Could not load report: ${error.response?.data?.message ?? error.message}`;

            return;
        }

        this._renderReportCards(analytics.data, attendance.data, timeline.data, heatmap.data, report.data);
        this._renderReportMenu(report.data.phase4_tables ?? []);
        this._renderDiagnostics(report.data.device_diagnostics ?? { events: [] });
        this._renderGeofenceActivity(report.data.geofence_activity ?? []);

        output.textContent = JSON.stringify({
            report: report.data,
            analytics: analytics.data,
            attendance: attendance.data,
            timeline: timeline.data.slice(0, 25),
            heatmap: heatmap.data.summary,
        }, null, 2);

        const chartPayload = { report: report.data, analytics: analytics.data, attendance: attendance.data, timeline: timeline.data, heatmap: heatmap.data };

        if (!this.reportCharts) {
            const root = document.getElementById('live-report-chart-root');
            const contentBlock = document.getElementById('live-report-content-block');

            if (contentBlock) contentBlock.classList.remove('d-none');

            this.reportCharts = mountReportCharts('live-report', root, contentBlock, chartPayload);
        } else {
            this.reportCharts.render(chartPayload);
        }
    }

    _renderReportCards(analytics, attendance, timeline, heatmap, report) {
        const internetOutages = report.device_diagnostics?.internet_outages ?? [];
        const gpsOutages = report.device_diagnostics?.gps_outages ?? [];
        const internetDownMinutes = Math.round(internetOutages.reduce((sum, o) => sum + o.duration_seconds, 0) / 60);

        const cards = [
            ['Distance', `${analytics.total_distance_km} km`],
            ['Moving', `${Math.round((analytics.moving_seconds ?? 0) / 60)} min`],
            ['Idle', `${Math.round((analytics.idle_seconds ?? 0) / 60)} min`],
            ['Attendance', attendance.status],
            ['Work Hours', `${attendance.working_hours} h`],
            ['Timeline', `${timeline.length} events`],
            ['Heat Zones', `${heatmap.summary?.clusters ?? 0}`],
            ['Max Speed', `${analytics.maximum_speed_mps ?? '—'} m/s`],
            ['Internet Outages', `${internetOutages.length} (${internetDownMinutes} min down)`],
            ['GPS Outages', `${gpsOutages.length}`],
            ['Geofence Events', `${report.geofence_activity?.length ?? 0}`],
        ];

        document.getElementById('live-report-cards').innerHTML = cards.map(([label, value]) => `
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

    _renderDiagnostics(deviceDiagnostics) {
        const rows = deviceDiagnostics.events ?? [];
        const tbody = document.getElementById('live-report-diagnostics');

        if (!tbody) return;

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

    _renderGeofenceActivity(activity) {
        const tbody = document.getElementById('live-report-geofence');

        if (!tbody) return;

        tbody.innerHTML = activity.length
            ? activity.map((event) => `
                <tr>
                    <td>${event.geofence_name ?? '—'}</td>
                    <td>${event.type}</td>
                    <td>${new Date(event.occurred_at).toLocaleString()}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="3" class="text-muted">No geofence activity in this range.</td></tr>';
    }

    _renderReportMenu(tables) {
        document.getElementById('live-report-menu').innerHTML = tables.map((table) => `
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
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('live-map');

    if (!container) return;

    const dataEl = document.getElementById('live-map-data');
    const people = dataEl ? JSON.parse(dataEl.textContent) : [];

    new LiveMap('live-map', people);

});
