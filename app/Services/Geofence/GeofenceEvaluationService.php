<?php

namespace App\Services\Geofence;

use App\Enums\GeofenceEventType;
use App\Events\GeofenceEventOccurred;
use App\Models\GeofenceEvent;
use App\Models\GpsLocation;
use App\Models\Geofence;

class GeofenceEvaluationService
{
    public function __construct(
        protected GeofenceGeometryService $geometry,
        protected GeofenceAssignmentComplianceService $compliance,
    ) {}

    public function evaluate(GpsLocation $location): void
    {
        $geofences = Geofence::active()->with('points')->get();

        if ($geofences->isEmpty()) {
            return;
        }

        $latestEvents = GeofenceEvent::query()
            ->where('user_id', $location->user_id)
            ->whereIn('geofence_id', $geofences->pluck('id'))
            ->orderByDesc('occurred_at')
            ->get()
            ->groupBy('geofence_id')
            ->map(fn ($events) => $events->first());

        foreach ($geofences as $geofence) {
            $this->evaluateGeofence($geofence, $location, $latestEvents->get($geofence->id));
        }
    }

    private function evaluateGeofence(Geofence $geofence, GpsLocation $location, ?GeofenceEvent $lastEvent): void
    {
        $isInside = $this->geometry->containsPoint($geofence, (float) $location->latitude, (float) $location->longitude);
        $wasInside = $lastEvent?->type === GeofenceEventType::Entered;

        if ($isInside === $wasInside) {
            return;
        }

        $event = GeofenceEvent::create([
            'geofence_id' => $geofence->id,
            'user_id' => $location->user_id,
            'gps_location_id' => $location->id,
            'type' => $isInside ? GeofenceEventType::Entered : GeofenceEventType::Exited,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'occurred_at' => $location->recorded_at,
        ]);

        broadcast(new GeofenceEventOccurred($event->setRelation('geofence', $geofence)));

        $this->compliance->handleGeofenceEvent($event, $geofence);
    }
}
