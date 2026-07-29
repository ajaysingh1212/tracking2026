<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    if ((int) $user->id === (int) $id) {
        return true;
    }

    if ($user->hasAnyRole(['Super Admin', 'Admin', 'Manager'])) {
        return true;
    }

    if (Conversation::query()
        ->whereHas('members', fn ($query) => $query->where('user_id', $user->id))
        ->whereHas('members', fn ($query) => $query->where('user_id', $id))
        ->exists()) {
        return true;
    }

    return $user->trackedUsers()
        ->where('tracked_user_id', $id)
        ->where('status', 'active')
        ->exists();
});

Broadcast::channel('conversation.{uuid}', function ($user, $uuid) {
    return Conversation::where('uuid', $uuid)->first()?->hasMember($user) ?? false;
});
