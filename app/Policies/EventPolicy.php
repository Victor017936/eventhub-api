<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Event $event): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->role === UserRole::Admin;
    }
}
