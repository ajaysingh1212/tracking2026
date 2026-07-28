import './bootstrap';

const OFFLINE_QUEUE_KEY = 'tracker.gps.offline_queue';
const HEARTBEAT_SECONDS = 100;
const FLUSH_RETRY_MS = 30000;

/**
 * Adaptive distance table from the Phase 2 spec: standing gets no
 * distance-triggered updates (heartbeat only), and required movement grows
 * with speed so a car/highway trip doesn't spam a point every few meters.
 */
function adaptiveThresholdForSpeed(speedMps) {
    if (speedMps === null || speedMps < 0.3) return null; // standing
    if (speedMps < 2) return 5; // walking
    if (speedMps < 8) return 10; // bike / running
    if (speedMps < 20) return 25; // car
    return 50; // highway
}

function haversineMeters(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const toRad = (d) => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function readQueue() {
    try {
        return JSON.parse(localStorage.getItem(OFFLINE_QUEUE_KEY) || '[]');
    } catch (e) {
        return [];
    }
}

function writeQueue(queue) {
    localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
}

export class GpsWatcher {
    constructor({ onStatus } = {}) {
        this.onStatus = onStatus || (() => {});
        this.watchId = null;
        this.distanceFilterMeters = 25;
        this.lastSentAt = null;
        this.lastSentPosition = null;
        this.flushTimer = null;
        this.permissionStatus = null;
    }

    async start() {
        if (!('geolocation' in navigator)) {
            this.onStatus({ state: 'unsupported', message: 'This browser does not support geolocation.' });
            return;
        }

        try {
            const { data } = await window.axios.get('/api/v1/me');
            this.distanceFilterMeters = data.distance_filter_meters ?? 25;
        } catch (e) {
            // Keep the default; the server-side optimizer is authoritative anyway.
        }

        this._watchPermission();

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this._handlePosition(position),
            (error) => this._handleError(error),
            { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 },
        );

        document.addEventListener('visibilitychange', this._onVisibilityChange);
        window.addEventListener('online', this._onOnline);
        window.addEventListener('beforeunload', this._onBeforeUnload);

        this.flushTimer = window.setInterval(() => this._flushQueue(), FLUSH_RETRY_MS);

        // Not "sharing" yet — that's only true once a position actually
        // arrives (see _handlePosition/_send). Saying "Sharing" here, before
        // the browser has even resolved a GPS fix, is misleading when the
        // fix is slow, denied, or never arrives.
        this.onStatus({ state: 'waiting' });
    }

    stop() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        document.removeEventListener('visibilitychange', this._onVisibilityChange);
        window.removeEventListener('online', this._onOnline);
        window.removeEventListener('beforeunload', this._onBeforeUnload);

        if (this.flushTimer) {
            window.clearInterval(this.flushTimer);
            this.flushTimer = null;
        }

        this.onStatus({ state: 'stopped' });
    }

    _watchPermission() {
        if (!navigator.permissions?.query) return;

        navigator.permissions.query({ name: 'geolocation' }).then((status) => {
            this.permissionStatus = status;
            this._reportDiagnostic(status.state === 'granted' ? 'permission_granted' : 'permission_revoked');

            status.onchange = () => {
                this._reportDiagnostic(status.state === 'granted' ? 'permission_granted' : 'permission_revoked');
            };
        }).catch(() => {});
    }

    _handlePosition(position) {
        const { latitude, longitude, speed, heading, accuracy, altitude } = position.coords;
        const now = Date.now();

        const distance = this.lastSentPosition
            ? haversineMeters(this.lastSentPosition.lat, this.lastSentPosition.lng, latitude, longitude)
            : null;

        const adaptive = adaptiveThresholdForSpeed(speed);
        const threshold = Math.max(adaptive ?? this.distanceFilterMeters, this.distanceFilterMeters);
        const secondsSinceLastSend = this.lastSentAt ? (now - this.lastSentAt) / 1000 : Infinity;

        const shouldSend = distance === null
            || distance >= threshold
            || (adaptive === null && secondsSinceLastSend >= HEARTBEAT_SECONDS);

        if (!shouldSend) {
            this.onStatus({ state: 'idle', accuracy, skipped: true });
            return;
        }

        if (accuracy !== null && accuracy !== undefined && accuracy > 100) {
            this._reportDiagnostic('poor_accuracy', { latitude, longitude });
        }

        const packet = {
            device_id: null,
            source_type: 'browser',
            latitude,
            longitude,
            accuracy,
            speed,
            bearing: heading,
            heading,
            altitude,
            battery_level: null,
            network_type: navigator.connection?.effectiveType ?? null,
            is_mock: false,
            recorded_at: new Date(position.timestamp).toISOString(),
        };

        this.lastSentAt = now;
        this.lastSentPosition = { lat: latitude, lng: longitude };

        this._send(packet);
    }

    _send(packet) {
        if (!navigator.onLine) {
            this._enqueue(packet);
            this.onStatus({ state: 'queued-offline' });
            return;
        }

        window.axios.post('/api/v1/gps/locations', packet)
            .then((response) => {
                this.onStatus({ state: 'sent', accepted: response.data.accepted });
            })
            .catch(() => {
                this._enqueue(packet);
                this.onStatus({ state: 'queued-error' });
            });
    }

    _enqueue(packet) {
        const queue = readQueue();
        queue.push(packet);
        writeQueue(queue);
    }

    _flushQueue() {
        const queue = readQueue();

        if (queue.length === 0 || !navigator.onLine) {
            return;
        }

        window.axios.post('/api/v1/gps/locations/batch', { locations: queue })
            .then(() => {
                writeQueue([]);
                this.onStatus({ state: 'flushed', count: queue.length });
            })
            .catch(() => {
                // Leave the queue intact; try again on the next timer tick or online event.
            });
    }

    _handleError(error) {
        if (error.code === error.PERMISSION_DENIED) {
            this._reportDiagnostic('permission_revoked');
        } else {
            this._reportDiagnostic('gps_disabled', { reason: error.message });
        }

        this.onStatus({ state: 'error', message: error.message });
    }

    _reportDiagnostic(eventType, extra = {}) {
        window.axios.post('/api/v1/gps/diagnostics', { event_type: eventType, ...extra }).catch(() => {});
    }

    _onVisibilityChange = () => {
        this._reportDiagnostic(document.hidden ? 'browser_hidden' : 'browser_visible');
    };

    _onOnline = () => {
        this._reportDiagnostic('internet_on');
        this._flushQueue();
    };

    _onBeforeUnload = () => {
        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify({ event_type: 'browser_closed' })], { type: 'application/json' });
            navigator.sendBeacon('/api/v1/gps/diagnostics', blob);
        }
    };
}

window.GpsWatcher = GpsWatcher;

const STATUS_LABELS = {
    waiting: 'Waiting for GPS fix…',
    idle: 'Sharing (no movement yet)',
    sent: 'Sharing',
    'queued-offline': 'Sharing (offline, queued)',
    'queued-error': 'Sharing (retrying)',
    flushed: 'Sharing',
    stopped: 'Stopped',
    error: 'Error — check location permission',
    unsupported: 'Not supported',
};

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('location-sharing-toggle');

    if (!toggle) return;

    const toggleLabel = document.getElementById('location-sharing-toggle-label');
    const statusEl = document.getElementById('location-sharing-status');
    const accuracyEl = document.getElementById('location-sharing-accuracy');
    const lastSentEl = document.getElementById('location-sharing-last-sent');

    let watcher = null;
    let sharing = false;

    const applyStatus = ({ state, accuracy }) => {
        if (statusEl) statusEl.textContent = STATUS_LABELS[state] ?? state;
        if (accuracy !== undefined && accuracy !== null && accuracyEl) {
            accuracyEl.textContent = `${Math.round(accuracy)} m`;
        }
        if ((state === 'sent' || state === 'flushed') && lastSentEl) {
            lastSentEl.textContent = new Date().toLocaleTimeString();
        }
    };

    toggle.addEventListener('click', () => {
        if (!sharing) {
            watcher = new GpsWatcher({ onStatus: applyStatus });
            watcher.start();
            sharing = true;
            if (toggleLabel) toggleLabel.textContent = 'Stop Sharing';
        } else {
            watcher?.stop();
            sharing = false;
            if (toggleLabel) toggleLabel.textContent = 'Start Sharing';
            if (statusEl) statusEl.textContent = 'Stopped';
        }
    });
});
