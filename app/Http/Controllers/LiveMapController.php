<?php

namespace App\Http\Controllers;

use App\Models\GpsLocation;
use App\Models\User;
use App\Services\UserPresenceService;
use Illuminate\View\View;

class LiveMapController extends Controller
{
    // Keep in sync with GpsLocationUpdated::MOVING_SPEED_MPS / StoreGpsLocationJob::STOP_SPEED_THRESHOLD.
    private const MOVING_SPEED_MPS = 0.5;

    public function __construct(
        protected UserPresenceService $presenceService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Manager'])) {
            $visibleUsers = User::query()->where('id', '!=', $user->id)->get(['id', 'name']);
        } else {
            $visibleUsers = User::query()
                ->whereIn('id', $user->trackedUsers()->where('status', 'active')->pluck('tracked_user_id'))
                ->get(['id', 'name']);
        }

        $people = $visibleUsers->map(function (User $person) {
            $location = GpsLocation::where('user_id', $person->id)->latest('recorded_at')->first();
            $speed = $location?->speed !== null ? (float) $location->speed : null;

            return [
                'id' => $person->id,
                'name' => $person->name,
                'lat' => $location ? (float) $location->latitude : null,
                'lng' => $location ? (float) $location->longitude : null,
                'speed' => $speed,
                'bearing' => $location?->bearing !== null ? (float) $location->bearing : null,
                'battery' => $location?->battery_level,
                'movementStatus' => $speed !== null && $speed > self::MOVING_SPEED_MPS ? 'moving' : 'idle',
                'isOnline' => $this->presenceService->isOnline($person->id),
                'lastSeen' => $location?->recorded_at?->toIso8601String(),
                'lastActivity' => $this->presenceService->lastActivityAt($person->id),
            ];
        })->values();

        return view('live-map.index', [
            'people' => $people,
        ]);
    }
}
