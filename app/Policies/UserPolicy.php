<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Allow a user to update ONLY their own User record.
     */
    public function update(User $actor, User $subject): bool
    {
        // dd('actor: '.$actor->id.' subject: '.$subject->id);
        return (int) $actor->id === (int) $subject->id;
    }

    /**
     * (Optional) Let admins bypass all checks.
     * Return true to allow, false to deny, or null to fall back to ability methods.
     */
    public function before(User $actor, string $ability): ?bool
    {
        // If you have an is_admin flag (adjust as needed):
        // return $actor->is_admin ? true : null;

        return null; // keep default
    }
}
