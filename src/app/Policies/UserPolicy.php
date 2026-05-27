<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function edit(User $authUser, User $targetUser): bool
    {
        return match ($authUser->role) {
            UserRole::Administrator => true,
            UserRole::Manager      => $targetUser->role === UserRole::User,
            UserRole::User         => $authUser->id === $targetUser->id,
        };
    }
}
