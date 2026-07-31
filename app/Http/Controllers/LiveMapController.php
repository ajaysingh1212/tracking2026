<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\DeviceStatus;
use App\Models\GeofenceAssignment;
use App\Models\GeofenceAssignmentRun;
use App\Models\GpsLocation;
use App\Models\User;
use App\Modules\Reports\MonitoringReportAccessService;
use App\Services\UserPresenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class LiveMapController extends Controller
{
    // Keep in sync with GpsLocationUpdated::MOVING_SPEED_MPS / StoreGpsLocationJob::STOP_SPEED_THRESHOLD.
    private const MOVING_SPEED_MPS = 0.5;

    public function __construct(
        protected UserPresenceService $presenceService,
        protected MonitoringReportAccessService $access,
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        $visibleUsers = $this->access->visibleUsers($user);

        // The viewer should also see themselves on their own live map (their own
        // location/status), plus who is currently tracking them.
        $people = $visibleUsers->push($user)->unique('id')
            ->map(function (User $person) use ($user) {
                $location = GpsLocation::where('user_id', $person->id)->latest('recorded_at')->first();
                $deviceStatus = DeviceStatus::query()
                    ->whereHas('deviceSession', fn (Builder $query) => $query->where('user_id', $person->id))
                    ->latest('last_ping_at')
                    ->first();
                $speed = $location?->speed !== null ? (float) $location->speed : null;
                $isOnline = $this->presenceService->isOnline($person->id);
                $isSelf = $person->id === $user->id;

                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'isSelf' => $isSelf,
                    'trackedBy' => $isSelf ? $this->trackedByNames($person) : [],
                    'lat' => $location ? (float) $location->latitude : null,
                    'lng' => $location ? (float) $location->longitude : null,
                    'speed' => $speed,
                    'bearing' => $location?->bearing !== null ? (float) $location->bearing : null,
                    'battery' => $deviceStatus?->battery_level ?? $location?->battery_level,
                    'gpsEnabled' => $deviceStatus?->is_gps_enabled,
                    'internetEnabled' => $deviceStatus?->is_internet_enabled,
                    'networkType' => $deviceStatus?->network_type ?? $location?->network_type,
                    'movementStatus' => $speed !== null && $speed > self::MOVING_SPEED_MPS ? 'moving' : 'idle',
                    'isOnline' => $isOnline,
                    'lastSeen' => $location?->recorded_at?->toIso8601String(),
                    'lastActivity' => $this->presenceService->lastActivityAt($person->id),
                    'geofences' => $this->geofencesFor($person),
                ];
            })
            // Offline people still matter here — show their last known fix (greyed out
            // marker) rather than hiding them the moment they go offline. Only drop
            // someone who has never reported a location at all (nothing to plot).
            ->filter(fn (array $person) => $person['lat'] !== null && $person['lng'] !== null)
            ->values();

        return view('live-map.index', [
            'people' => $people,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function trackedByNames(User $person): array
    {
        return $person->trackerRelations()
            ->where('status', UserStatus::Active)
            ->with('trackerUser:id,name')
            ->get()
            ->pluck('trackerUser.name')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function geofencesFor(User $person): array
    {
        return GeofenceAssignment::active()
            ->where('user_id', $person->id)
            ->with(['geofence.points', 'runs' => fn ($query) => $query->whereDate('run_date', today())])
            ->orderBy('route_label')
            ->orderBy('sequence')
            ->get()
            ->filter(fn (GeofenceAssignment $assignment) => $assignment->geofence !== null)
            ->map(function (GeofenceAssignment $assignment) {
                /** @var GeofenceAssignmentRun|null $run */
                $run = $assignment->runs->first();

                return [
                    'assignment_uuid' => $assignment->uuid,
                    'name' => $assignment->geofence->name,
                    'type' => $assignment->geofence->type->value,
                    'color' => $assignment->geofence->color,
                    'route_label' => $assignment->route_label,
                    'sequence' => $assignment->sequence,
                    'schedule_type' => $assignment->schedule_type->value,
                    'schedule_days' => $assignment->schedule_days,
                    'schedule_date' => $assignment->schedule_date?->toDateString(),
                    'window_start' => $assignment->window_start,
                    'window_end' => $assignment->window_end,
                    'compliance_status' => $run?->status->value ?? 'pending',
                    'visited_at' => $run?->visited_at?->toIso8601String(),
                    'notified_at' => $run?->notified_at?->toIso8601String(),
                    'center_lat' => $assignment->geofence->center_lat,
                    'center_lng' => $assignment->geofence->center_lng,
                    'radius_meters' => $assignment->geofence->radius_meters,
                    'points' => $assignment->geofence->points->map(fn ($point) => [
                        'latitude' => $point->latitude,
                        'longitude' => $point->longitude,
                    ]),
                ];
            })
            ->values()
            ->all();
    }
}
