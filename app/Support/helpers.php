<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Subscription;

/**
 * Determine if the (authenticated) subscriber is subscribed to the given creator.
 *
 * @param  \App\Models\User  $creator  The creator user (owner of the plan/content)
 * @param  \App\Models\User|null $viewer Optional override for the subscriber (defaults to auth user)
 * @return bool
 */
function subscribed_to_creator(User $creator, ?User $viewer = null): bool
{
    $viewer = $viewer ?: Auth::user();
    if (!$viewer) {
        return false; // not logged in
    }

    // Avoid creator subscribing to self (optional)
    if ($viewer->id === $creator->id) {
        return false;
    }

    // Cache briefly to minimize repeated DB queries per request bursts
    $cacheKey = sprintf('subscribed:%d:%d', $viewer->id, $creator->id);

    return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($viewer, $creator) {

        // Define which statuses count as "active access" in your app
        $activeStatuses = ['active', 'trialing', 'past_due']; 
        // If you only want strictly active, use ['active'].

        return Subscription::query()
            ->where('user_id', $viewer->id)     // subscriber
            ->where('creator_id', $creator->id) // creator
            ->whereIn('status', $activeStatuses)
            // treat as active while current period is not ended
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                  ->orWhere('current_period_end', '>', now());
            })
            // if you support "cancel at period end", it still grants access until period end
            // ->where(function ($q) { $q->whereNull('cancel_at_period_end')->orWhere('cancel_at_period_end', false); })
            ->exists();
    });
}
