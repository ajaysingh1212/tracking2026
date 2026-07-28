<?php

namespace App\Services\Gps;

use App\DTO\LocationUpdateData;
use App\Models\DeviceStatus;

/**
 * Decides whether an incoming GPS packet represents "meaningful movement"
 * worth persisting, or is noise/redundant relative to the device's last
 * accepted point. Kept pure (no DB reads/writes of its own) so it stays
 * trivially unit-testable.
 */
class CoordinateOptimizerService
{
    private const EARTH_RADIUS_METERS = 6371000;

    private const KEEPALIVE_SECONDS = 120;

    private const BEARING_DELTA_THRESHOLD = 30.0;

    private const SPEED_DELTA_THRESHOLD = 5.0;

    public function shouldAccept(?DeviceStatus $status, LocationUpdateData $incoming, int $distanceFilterMeters): OptimizerResult
    {
        $last = $status?->lastLocation;

        if (! $last) {
            return OptimizerResult::accept();
        }

        $distance = $this->distanceInMeters(
            (float) $last->latitude,
            (float) $last->longitude,
            $incoming->latitude,
            $incoming->longitude,
        );

        if ($distance >= $distanceFilterMeters) {
            return OptimizerResult::accept();
        }

        if (abs($incoming->recordedAt->diffInSeconds($last->recorded_at)) >= self::KEEPALIVE_SECONDS) {
            return OptimizerResult::accept();
        }

        if ($incoming->bearing !== null && $last->bearing !== null) {
            if ($this->bearingDelta($incoming->bearing, (float) $last->bearing) > self::BEARING_DELTA_THRESHOLD) {
                return OptimizerResult::accept();
            }
        }

        if ($incoming->speed !== null && $last->speed !== null) {
            if (abs($incoming->speed - (float) $last->speed) > self::SPEED_DELTA_THRESHOLD) {
                return OptimizerResult::accept();
            }
        }

        return OptimizerResult::reject('throttled');
    }

    public function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    private function bearingDelta(float $a, float $b): float
    {
        $diff = abs($a - $b);

        return $diff > 180 ? 360 - $diff : $diff;
    }
}
