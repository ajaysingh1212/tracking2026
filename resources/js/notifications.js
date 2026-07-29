const CATEGORY_ICONS = {
    message: 'fa-solid fa-comment',
    mention: 'fa-solid fa-at',
    call: 'fa-solid fa-phone',
    tracking: 'fa-solid fa-location-dot',
    diagnostic: 'fa-solid fa-triangle-exclamation',
    system: 'fa-regular fa-bell',
};

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}

function playBeep() {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContextClass();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        oscillator.stop(ctx.currentTime + 0.35);
    } catch (e) {
        // Web Audio unsupported/blocked — silently skip the sound alert.
    }
}

function notificationRowHtml(payload) {
    const icon = CATEGORY_ICONS[payload.category] ?? CATEGORY_ICONS.system;

    return `
        <a href="#" class="tracker-notification-item unread" data-notification-id="${payload.id}" data-action-url="${escapeHtml(payload.action_url ?? '')}">
            <div class="tracker-notification-icon tracker-notification-icon-${payload.category ?? 'system'}">
                <i class="${icon}"></i>
            </div>
            <div class="flex-fill">
                <div class="tracker-notification-message">${escapeHtml(payload.message)}</div>
                <span class="small text-muted">just now</span>
            </div>
        </a>
    `;
}

async function markRead(id) {
    if (!id) return;

    await window.axios.post(`/notifications/${id}/read`).catch(() => {});
}

function bindRowClicks(container) {
    container.addEventListener('click', (event) => {
        const row = event.target.closest('[data-notification-id]');

        if (!row) return;

        event.preventDefault();

        const id = row.dataset.notificationId;
        const actionUrl = row.dataset.actionUrl;

        markRead(id).finally(() => {
            if (actionUrl) {
                window.location.href = actionUrl;
            } else {
                row.classList.remove('unread');
            }
        });
    });
}

export function initNotificationCenter() {
    const userId = window.__trackerUserId;

    if (!userId || !window.Echo) return;

    const badgeEl = document.getElementById('notification-bell-badge');
    const dropdownListEl = document.getElementById('notification-dropdown-list');
    const enableBtn = document.getElementById('enable-desktop-notifications-btn');

    [dropdownListEl, document.getElementById('notification-index-list')]
        .filter(Boolean)
        .forEach(bindRowClicks);

    enableBtn?.addEventListener('click', () => {
        window.Notification?.requestPermission().then((permission) => {
            enableBtn.textContent = permission === 'granted' ? 'Desktop notifications enabled' : 'Permission not granted';
        });
    });

    window.Echo.private(`App.Models.User.${userId}`).notification((payload) => {
        if (badgeEl) {
            const current = parseInt(badgeEl.textContent || '0', 10) || 0;
            badgeEl.textContent = current + 1;
            badgeEl.classList.remove('d-none');
        }

        if (dropdownListEl) {
            const empty = dropdownListEl.querySelector('[data-notification-empty]');
            empty?.remove();
            dropdownListEl.insertAdjacentHTML('afterbegin', notificationRowHtml(payload));
        }

        playBeep();

        if (document.hidden && window.Notification && window.Notification.permission === 'granted') {
            const native = new window.Notification('Tracker Enterprise', { body: payload.message });
            native.onclick = () => {
                window.focus();
                if (payload.action_url) window.location.href = payload.action_url;
            };
        } else if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 4000,
                showConfirmButton: false,
                icon: 'info',
                title: payload.message,
            });
        }
    });
}
