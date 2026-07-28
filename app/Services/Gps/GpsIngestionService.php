<?php

namespace App\Services\Gps;

use App\DTO\LocationUpdateData;
use App\Enums\DiagnosticEventType;
use App\Enums\TrackingSessionStatus;
use App\Events\LocationReceived;
use App\Events\LocationValidated;
use App\Jobs\StoreGpsLocationJob;
use App\Models\DeviceSession;
use App\Models\DiagnosticLog;
use App\Models\DeviceStatus;
use App\Models\TrackingSession;
use App\Models\User;

class GpsIngestionService
{
    private const SESSION_GAP_SECONDS = 600;

    private const POOR_ACCURACY_METERS = 100.0;

    public function __construct(
        protected CoordinateOptimizerService $optimizer,
    ) {}

    /**
     * @param  bool  $async  Live single-ping ingestion queues the write to keep
     *                       request latency low. Batch/offline-sync processes
     *                       synchronously instead: those points must be stored
     *                       (and device_status updated) before the *next* point
     *                       in the same batch is optimizer-checked, otherwise
     *                       every point in a batch would see stale "last
     *                       location" state and never get throttled correctly.
     */
    public function ingest(User $user, LocationUpdateData $dto, bool $async = true): IngestResult
    {
        $deviceSession = $this->resolveDeviceSession($user, $dto);
        $status = $deviceSession->status;

        $this->recordPacketDiagnostics($user, $deviceSession, $dto);

        LocationReceived::dispatch($user, $dto);
        LocationValidated::dispatch($user, $dto);

        $distanceFilterMeters = $user->trackingPreference?->distance_filter_meters ?? 25;

        $decision = $this->optimizer->shouldAccept($status, $dto, $distanceFilterMeters);

        if (! $decision->accepted) {
            return IngestResult::rejected($deviceSession->id, $decision->reason);
        }

        $trackingSession = $this->resolveTrackingSession($user, $deviceSession, $status, $dto);

        if ($async) {
            StoreGpsLocationJob::dispatch($user->id, $deviceSession->id, $trackingSession->id, $dto)
                ->onConnection('redis');
        } else {
            (new StoreGpsLocationJob($user->id, $deviceSession->id, $trackingSession->id, $dto))
                ->handle($this->optimizer);
        }

        return IngestResult::accepted($deviceSession->id, $trackingSession->id, $trackingSession->uuid);
    }

    private function resolveDeviceSession(User $user, LocationUpdateData $dto): DeviceSession
    {
        return DeviceSession::query()->firstOrCreate(
            ['user_id' => $user->id, 'session_id' => $dto->deviceId],
            [
                'device_name' => null,
                'platform' => $dto->sourceType->value,
                'is_current' => true,
                'last_login_at' => now(),
                'last_activity_at' => now(),
            ],
        );
    }

    private function resolveTrackingSession(User $user, DeviceSession $deviceSession, ?DeviceStatus $status, LocationUpdateData $dto): TrackingSession
    {
        if ($dto->trackingSessionUuid) {
            $existing = TrackingSession::query()
                ->where('uuid', $dto->trackingSessionUuid)
                ->where('device_session_id', $deviceSession->id)
                ->first();

            if ($existing && $existing->status === TrackingSessionStatus::Active) {
                return $existing;
            }
        }

        $lastPingAt = $status?->last_ping_at;
        $gapExceeded = ! $lastPingAt || $dto->recordedAt->diffInSeconds($lastPingAt, true) >= self::SESSION_GAP_SECONDS;

        $active = TrackingSession::query()
            ->where('device_session_id', $deviceSession->id)
            ->where('status', TrackingSessionStatus::Active)
            ->latest('started_at')
            ->first();

        if ($active && ! $gapExceeded) {
            return $active;
        }

        if ($active && $gapExceeded) {
            $active->update([
                'status' => TrackingSessionStatus::Ended,
                'ended_at' => $lastPingAt ?? now(),
            ]);
        }

        return TrackingSession::create([
            'user_id' => $user->id,
            'device_session_id' => $deviceSession->id,
            'source_type' => $dto->sourceType,
            'started_at' => $dto->recordedAt,
            'status' => TrackingSessionStatus::Active,
        ]);
    }

    private function recordPacketDiagnostics(User $user, DeviceSession $deviceSession, LocationUpdateData $dto): void
    {
        if ($dto->isMock) {
            $this->diagnostic($user, $deviceSession, DiagnosticEventType::MockGpsDetected, $dto);
        }

        if ($dto->accuracy !== null && $dto->accuracy > self::POOR_ACCURACY_METERS) {
            $this->diagnostic($user, $deviceSession, DiagnosticEventType::PoorAccuracy, $dto);
        }
    }

    private function diagnostic(User $user, DeviceSession $deviceSession, DiagnosticEventType $type, LocationUpdateData $dto): void
    {
        DiagnosticLog::create([
            'user_id' => $user->id,
            'device_session_id' => $deviceSession->id,
            'event_type' => $type,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
            'occurred_at' => $dto->recordedAt,
        ]);
    }
}
