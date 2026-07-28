<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    if ((int) $user->id === (int) $id) {
        return true;
    }

    if ($user->hasAnyRole(['Super Admin', 'Admin', 'Manager'])) {
        return true;
    }

    return $user->trackedUsers()
        ->where('tracked_user_id', $id)
        ->where('status', 'active')
        ->exists();
});
