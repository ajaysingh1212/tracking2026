<?php

namespace App\Services;

use App\Models\TrackingRelation;
use App\Models\User;
use App\Models\UserLicense;
use App\Notifications\TrackingRequestNotification;
use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TrackingRelationService
{
    public function __construct(
        protected SettingsService $settings,
        protected ActivityLogService $activityLogService,
    ) {}

    public function create(array $attributes): TrackingRelation
    {
        [$relation, $tracker] = DB::transaction(function () use ($attributes): array {
            $tracker = User::findOrFail($attributes['tracker_user_id']);
            $trackedUserId = (int) $attributes['tracked_user_id'];
            $trashed = TrackingRelation::withTrashed()
                ->where('tracker_user_id', $tracker->id)
                ->where('tracked_user_id', $trackedUserId)
                ->lockForUpdate()
                ->first();

            $license = $trashed?->user_license_id
                ? UserLicense::query()->whereKey($trashed->user_license_id)->lockForUpdate()->first()
                : null;

            if ($license && $license->expiry_date && $license->expiry_date->isPast()) {
                $license = null;
            }

            if (! $license) {
                $license = UserLicense::query()
                    ->where('user_id', $tracker->id)
                    ->where('payment_status', PaymentStatus::Paid)
                    ->whereIn('status', [LicenseStatus::Pending, LicenseStatus::Active])
                    ->where(function ($query) use ($trackedUserId): void {
                        $query->whereNull('assigned_tracked_user_id')
                            ->orWhere('assigned_tracked_user_id', $trackedUserId);
                    })
                    ->where(function ($query): void {
                        $query->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
                    })
                    ->orderByRaw('assigned_tracked_user_id IS NULL')
                    ->latest('purchase_date')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $license) {
                throw ValidationException::withMessages([
                    'tracker_user_id' => 'No valid license is available for this tracked user. Purchase a license to continue.',
                ]);
            }

            if ($license->assigned_tracked_user_id && (int) $license->assigned_tracked_user_id !== $trackedUserId) {
                throw ValidationException::withMessages([
                    'tracker_user_id' => 'This license is already assigned to another tracked user.',
                ]);
            }

            $license->assigned_tracked_user_id = $trackedUserId;
            if ($license->status === LicenseStatus::Pending) {
                app(LicenseService::class)->activateForUse($license, 'tracking');
            }
            $license->save();

            $attributes['user_license_id'] = $license->id;
            if ($trashed?->trashed()) {
                $trashed->restore();
                $trashed->update($attributes);
                $relation = $trashed;
            } elseif ($trashed) {
                throw ValidationException::withMessages([
                    'tracked_user_id' => 'A tracking relation already exists for this user.',
                ]);
            } else {
                $relation = TrackingRelation::create($attributes);
            }

            return [$relation, $tracker];
        });

        $this->activityLogService->log(User::query()->find(Auth::id()), 'tracking_relation.created', $relation, [
            'tracker' => $tracker->name,
        ]);

        User::find($attributes['tracked_user_id'])?->notify(new TrackingRequestNotification($relation, $tracker));

        return $relation;
    }

    public function delete(TrackingRelation $relation): void
    {
        $relation->delete();

        $this->activityLogService->log(User::query()->find(Auth::id()), 'tracking_relation.deleted', null, [
            'relationship_name' => $relation->relationship_name,
            'license_id' => $relation->user_license_id,
        ]);
    }
}
