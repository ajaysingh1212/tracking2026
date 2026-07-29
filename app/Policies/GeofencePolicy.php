<?php

namespace App\Policies;

use App\Models\Geofence;
use App\Models\User;

class GeofencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage geofences');
    }

    public function view(User $user, Geofence $geofence): bool
    {
        return $user->can('manage geofences');
    }

    public function create(User $user): bool
    {
        return $user->can('manage geofences');
    }

    public function update(User $user, Geofence $geofence): bool
    {
        return $user->can('manage geofences');
    }

    public function delete(User $user, Geofence $geofence): bool
    {
        return $user->can('manage geofences');
    }
}
