<?php

namespace App\Services;

use App\Models\TrackingRelation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatAuthorizationService
{
    /**
     * Two users may only communicate if a tracking relationship exists
     * between them (in either direction), or the acting user ($a) can manage
     * tracking relations outright — mirroring the override already used
     * for private-channel access in routes/channels.php.
     *
     * This is intentionally NOT symmetric: only $a's permission is checked.
     * Every call site passes (actor, target) — if $b's permission also
     * bypassed the check, anyone could freely message/call/add any admin or
     * manager to a group without a tracking relationship, just because the
     * *target* happens to hold that permission.
     */
    public function canCommunicate(User $a, User $b): bool
    {
        if ($a->is($b)) {
            return true;
        }

        if ($a->can('manage tracking relations')) {
            return true;
        }

        return TrackingRelation::query()
            ->where('status', 'active')
            ->where(function ($query) use ($a, $b) {
                $query->where(function ($q) use ($a, $b) {
                    $q->where('tracker_user_id', $a->id)->where('tracked_user_id', $b->id);
                })->orWhere(function ($q) use ($a, $b) {
                    $q->where('tracker_user_id', $b->id)->where('tracked_user_id', $a->id);
                });
            })
            ->exists();
    }

    /**
     * Users this person can legally start a chat with — mirrors the
     * tracked/admin-override visibility rule already used inline in
     * LiveMapController::index(), but bidirectional like canCommunicate().
     *
     * @return Collection<int, User>
     */
    public function communicableUsers(User $user): Collection
    {
        if ($user->can('manage tracking relations')) {
            return User::query()->where('id', '!=', $user->id)->get();
        }

        $trackedIds = $user->trackedUsers()->where('status', 'active')->pluck('tracked_user_id');
        $trackerIds = $user->trackerRelations()->where('status', 'active')->pluck('tracker_user_id');

        return User::query()->whereIn('id', $trackedIds->merge($trackerIds)->unique())->get();
    }
}
