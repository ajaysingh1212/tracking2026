import './bootstrap';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const LIGHT_TILES = {
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
};

const DARK_TILES = {
    url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
};

const DEFAULT_CENTER = [20.5937, 78.9629];
const TWEEN_MS = 1000;

function markerIcon(initial, isOnline) {
    return L.divIcon({
        className: `tracker-map-marker ${isOnline ? '' : 'tracker-map-marker-offline'}`,
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

class LiveMap {
    constructor(containerId, people) {
        this.people = new Map(people.map((person) => [person.id, person]));
        this.markers = new Map();

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const tiles = isDark ? DARK_TILES : LIGHT_TILES;

        this.map = L.map(containerId, { zoomControl: true }).setView(DEFAULT_CENTER, 5);
        L.tileLayer(tiles.url, { attribution: tiles.attribution, maxZoom: 19 }).addTo(this.map);

        const withLocation = people.filter((p) => p.lat !== null && p.lng !== null);

        withLocation.forEach((person) => this._placeMarker(person));

        if (withLocation.length > 0) {
            const bounds = L.latLngBounds(withLocation.map((p) => [p.lat, p.lng]));
            this.map.fitBounds(bounds.pad(0.3));
        }

        people.forEach((person) => this._subscribe(person));
    }

    _placeMarker(person) {
        const marker = L.marker([person.lat, person.lng], {
            icon: markerIcon(person.name.charAt(0).toUpperCase(), person.isOnline),
        }).addTo(this.map);

        marker.bindPopup(this._popupHtml(person));
        this.markers.set(person.id, marker);
    }

    _popupHtml(person) {
        const statusClass = person.isOnline ? 'tracker-status-active' : 'tracker-status-muted';
        const statusLabel = person.isOnline ? 'Online' : 'Offline';

        return `
            <div class="tracker-map-popup">
                <strong>${person.name}</strong>
                <div class="tracker-map-popup-badges">
                    <span class="tracker-status-pill ${statusClass}">${statusLabel}</span>
                    ${person.isOnline ? `<span class="tracker-status-pill ${person.movementStatus === 'moving' ? 'tracker-status-active' : 'tracker-status-pending'}">${movementLabel(person.movementStatus)}</span>` : ''}
                </div>
                <div>Speed: ${formatSpeed(person.speed)}</div>
                <div>Battery: ${person.battery !== null && person.battery !== undefined ? person.battery + '%' : '—'}</div>
                <div>Last location: ${formatLastSeen(person.lastSeen)}</div>
            </div>
        `;
    }

    _subscribe(person) {
        const channel = window.Echo.private(`App.Models.User.${person.id}`);

        channel.listen('.gps.location.updated', (payload) => this._onLocationUpdate(person.id, payload));
        channel.listen('.presence.changed', (payload) => this._onPresenceChange(person.id, payload));
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
            movementStatus: payload.movement_status,
            isOnline: true,
            lastSeen: payload.recorded_at,
        });

        let marker = this.markers.get(personId);

        if (!marker) {
            marker = L.marker([to.lat, to.lng], { icon: markerIcon(person.name.charAt(0).toUpperCase(), true) }).addTo(this.map);
            marker.bindPopup(this._popupHtml(person));
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
            if (person.lat === null || person.lat === undefined) return;

            this.map.flyTo([person.lat, person.lng], 15);
            this.markers.get(personId)?.openPopup();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('live-map');

    if (!container) return;

    const dataEl = document.getElementById('live-map-data');
    const people = dataEl ? JSON.parse(dataEl.textContent) : [];

    new LiveMap('live-map', people);
});
