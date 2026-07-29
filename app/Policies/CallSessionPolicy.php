<?php

namespace App\Policies;

use App\Models\CallSession;
use App\Models\Conversation;
use App\Models\User;

class CallSessionPolicy
{
    public function view(User $user, CallSession $callSession): bool
    {
        return $callSession->loadMissing('conversation')->conversation->hasMember($user);
    }

    public function create(User $user, Conversation $conversation): bool
    {
        return $conversation->hasMember($user);
    }

    public function join(User $user, CallSession $callSession): bool
    {
        return $callSession->loadMissing('conversation')->conversation->hasMember($user);
    }
}
