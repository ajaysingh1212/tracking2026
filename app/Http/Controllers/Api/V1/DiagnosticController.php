<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DiagnosticEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DiagnosticEventRequest;
use App\Models\DeviceSession;
use App\Models\DiagnosticLog;
use App\Models\User;
use App\Notifications\DiagnosticAlertNotification;
use Illuminate\Http\JsonResponse;

class DiagnosticController extends Controller
{
    /**
     * Event types worth interrupting a tracker for — the rest (browser
     * visibility changes, normal permission grants, etc.) are routine
     * telemetry, not alerts.
     */
    private const CONCERNING_EVENTS = [
        DiagnosticEventType::MockGpsDetected,
        DiagnosticEventType::PoorAccuracy,
        DiagnosticEventType::HighBatteryDrain,
        DiagnosticEventType::WebSocketLost,
        DiagnosticEventType::PermissionRevoked,
        DiagnosticEventType::ServerTimeout,
        DiagnosticEventType::AutomationDetected,
    ];

    public function store(DiagnosticEventRequest $request): JsonResponse
    {
        $deviceSessionId = $request->hasSession()
            ? DeviceSession::query()
                ->where('user_id', $request->user()->id)
                ->where('session_id', $request->session()->getId())
                ->value('id')
            : null;

        $eventType = DiagnosticEventType::from($request->string('event_type')->toString());

        $log = DiagnosticLog::create([
            'user_id' => $request->user()->id,
            'device_session_id' => $deviceSessionId,
            'event_type' => $eventType,
            'latitude' => $request->filled('latitude') ? $request->float('latitude') : null,
            'longitude' => $request->filled('longitude') ? $request->float('longitude') : null,
            'reason' => $request->string('reason')->toString() ?: null,
            'duration_seconds' => $request->filled('duration_seconds') ? $request->integer('duration_seconds') : null,
            'network_type' => $request->string('network_type')->toString() ?: null,
            'battery_level' => $request->filled('battery_level') ? $request->integer('battery_level') : null,
            'occurred_at' => $request->filled('occurred_at') ? $request->date('occurred_at') : now(),
        ]);

        if (in_array($eventType, self::CONCERNING_EVENTS, true)) {
            $trackerIds = $request->user()->trackerRelations()->where('status', 'active')->pluck('tracker_user_id');

            User::query()->whereIn('id', $trackerIds)->get()->each(
                fn (User $tracker) => $tracker->notify(new DiagnosticAlertNotification($log, $request->user())),
            );
        }

        return response()->json(['message' => 'Recorded.'], 201);
    }
}
