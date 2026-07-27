<?php

namespace App\Services;

use App\Models\TrackingRelation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TrackingRelationService
{
    public function __construct(
        protected SettingsService $settings,
        protected ActivityLogService $activityLogService,
    ) {}

    public function create(array $attributes): TrackingRelation
    {
        $tracker = User::findOrFail($attributes['tracker_user_id']);
        $license = $tracker->userLicenses()->where('status', 'active')->latest('expiry_date')->first();

        if (! $license || $license->remaining_slots < 1) {
            throw ValidationException::withMessages([
                'tracker_user_id' => 'The selected tracker has no remaining license slots.',
            ]);
        }

        $trashed = TrackingRelation::withTrashed()
            ->where('tracker_user_id', $attributes['tracker_user_id'])
            ->where('tracked_user_id', $attributes['tracked_user_id'])
            ->onlyTrashed()
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update($attributes);
            $relation = $trashed;
        } else {
            $relation = TrackingRelation::create($attributes);
        }

        $license->decrement('remaining_slots');
        $license->increment('consumed_slots');

        $this->activityLogService->log(auth()->user(), 'tracking_relation.created', $relation, [
            'tracker' => $tracker->name,
        ]);

        return $relation;
    }

    public function delete(TrackingRelation $relation): void
    {
        $returnSlot = (bool) $this->settings->get('license', 'return_slots_on_delete', true);

        if ($returnSlot) {
            $license = $relation->trackerUser->userLicenses()->where('status', 'active')->latest('expiry_date')->first();

            if ($license) {
                $license->increment('remaining_slots');
                $license->decrement('consumed_slots');
            }
        }

        $relation->delete();

        $this->activityLogService->log(auth()->user(), 'tracking_relation.deleted', null, [
            'relationship_name' => $relation->relationship_name,
            'slot_returned' => $returnSlot,
        ]);
    }
}
