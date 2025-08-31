<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Create or update the single subscription owned by a creator.
     * Enforces "one subscription per creator".
     */
    public function createOrUpdateCreatorSubscription(User $creator, array $data): Subscription
    {
        // Optional: you can validate $data in a FormRequest instead.
        $payload = [
            'title'       => $data['title']       ?? 'Creator Plan',
            'description' => $data['description'] ?? null,
            'price'       => $data['price']       ?? 9.99,
            'interval'    => $data['interval']    ?? 'month',
        ];

        return DB::transaction(function () use ($creator, $payload) {
            // hasOne by creator_id unique index guarantees only one row
            $sub = Subscription::firstOrNew(['creator_id' => $creator->id]);
            $sub->fill($payload);
            $sub->creator_id = $creator->id;
            $sub->save();

            return $sub->fresh();
        });
    }

    /**
     * Subscribe a user to a subscription.
     * - Prevents self-subscription
     * - Prevents duplicate pivot rows (uses syncWithoutDetaching)
     * - Snapshots price
     * - Also sets subscriptions.user_id = $subscriber->id (with concurrency safety)
     */
    public function subscribe(User $subscriber, Subscription $subscription, ?Carbon $startsAt = null, array $providerMeta = []): void
    {
        if ($subscriber->id === (int) $subscription->creator_id) {
            throw ValidationException::withMessages([
                'subscription' => 'You cannot subscribe to your own subscription.',
            ]);
        }

        DB::transaction(function () use ($subscriber, $subscription, $startsAt, $providerMeta) {
            // Lock the target subscription row to avoid concurrent claims
            /** @var \App\Models\Subscription $sub */
            $sub = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            // If already linked to another user, block it
            if (!is_null($sub->user_id) && (int) $sub->user_id !== (int) $subscriber->id) {
                throw ValidationException::withMessages([
                    'subscription' => 'This subscription is already linked to a different user.',
                ]);
            }

            // Set/confirm ownership by this subscriber
            if ((int) $sub->user_id !== (int) $subscriber->id) {
                $sub->user_id = $subscriber->id;
                $sub->save();
            }

            $pivot = array_merge([
                'starts_at'                 => $startsAt?->toDateTimeString() ?? now(),
                'ends_at'                   => null,
                'status'                    => 'active',
                'is_active'                 => true,
                'price_snapshot'            => $sub->price, // snapshot plan price at subscribe time
                'provider'                  => $providerMeta['provider'] ?? null,
                'provider_subscription_id'  => $providerMeta['provider_subscription_id'] ?? null,
            ], $providerMeta);

            // Attach or update pivot without detaching other subscriptions
            $subscriber->subscriptions()->syncWithoutDetaching([
                $sub->id => $pivot,
            ]);

            // Optional domain event after successful write
            // event(new \App\Events\SubscriptionStarted($subscriber->id, $sub->id));
        });
    }


    /**
     * Cancel (soft) a subscription (keeps history).
     */
    public function cancel(User $subscriber, Subscription $subscription, ?Carbon $endsAt = null, array $providerMeta = []): void
    {
        DB::transaction(function () use ($subscriber, $subscription, $endsAt, $providerMeta) {
            $exists = $subscriber->subscriptions()->where('subscription_id', $subscription->id)->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'subscription' => 'You are not subscribed to this subscription.',
                ]);
            }

            $update = array_merge([
                'is_active' => false,
                'status'    => 'canceled',
                'ends_at'   => ($endsAt ?? now())->toDateTimeString(),
            ], $providerMeta);

            $subscriber->subscriptions()->updateExistingPivot($subscription->id, $update);

            // event(new \App\Events\SubscriptionCanceled($subscriber->id, $subscription->id));
        });
    }

    /**
     * Resume (reactivate) a previously canceled subscription.
     * You may want to verify payment with your provider before flipping this.
     */
    public function resume(User $subscriber, Subscription $subscription, ?Carbon $nextRenewal = null, array $providerMeta = []): void
    {
        DB::transaction(function () use ($subscriber, $subscription, $nextRenewal, $providerMeta) {
            $exists = $subscriber->subscriptions()->where('subscription_id', $subscription->id)->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'subscription' => 'You do not have a prior subscription to resume.',
                ]);
            }

            $update = array_merge([
                'is_active' => true,
                'status'    => 'active',
                'ends_at'   => $nextRenewal?->toDateTimeString(), // optional “paid through” date
            ], $providerMeta);

            $subscriber->subscriptions()->updateExistingPivot($subscription->id, $update);

            // event(new \App\Events\SubscriptionResumed($subscriber->id, $subscription->id));
        });
    }

    /**
     * Is the subscriber actively subscribed?
     */
    public function isSubscribed(User $subscriber, Subscription|int $subscription): bool
    {
        $subscriptionId = $subscription instanceof Subscription ? $subscription->id : (int) $subscription;

        return $subscriber->subscriptions()
            ->where('subscription_id', $subscriptionId)
            ->wherePivot('is_active', true)
            ->wherePivot('status', 'active')
            ->where(function ($q) {
                $q->whereNull('subscription_user.ends_at')
                  ->orWhere('subscription_user.ends_at', '>', now());
            })
            ->exists();
    }

    /**
     * Active subscribers for a creator.
     */
    public function creatorActiveSubscribers(User $creator): Collection
    {
        $plan = $creator->ownedSubscription()->first();
        if (! $plan) {
            return collect();
        }

        return $plan->subscribers()
            ->wherePivot('is_active', true)
            ->wherePivot('status', 'active')
            ->get();
    }

    /**
     * Active subscriptions for a subscriber (list of Subscription models).
     */
    public function subscriberActiveSubscriptions(User $subscriber): Collection
    {
        return $subscriber->subscriptions()
            ->wherePivot('is_active', true)
            ->wherePivot('status', 'active')
            ->get();
    }

    /**
     * Provider webhook sync (Stripe example).
     * Maps provider_subscription_id => pivot update.
     * Call this from your webhook controller after verifying signature.
     */
    public function syncFromProviderWebhook(string $provider, string $providerSubscriptionId, array $attributes): void
    {
        // Find all pivot rows with this provider sub id
        $rows = DB::table('subscription_user')
            ->where('provider', $provider)
            ->where('provider_subscription_id', $providerSubscriptionId)
            ->get(['user_id', 'subscription_id']);

        foreach ($rows as $row) {
            // Normalize incoming attributes: status, is_active, ends_at, etc.
            $update = array_filter([
                'status'    => $attributes['status']     ?? null,
                'is_active' => $attributes['is_active']  ?? null,
                'ends_at'   => isset($attributes['ends_at'])
                                ? Carbon::parse($attributes['ends_at'])->toDateTimeString()
                                : null,
            ], fn ($v) => ! is_null($v));

            if (! empty($update)) {
                DB::table('subscription_user')
                    ->where('user_id', $row->user_id)
                    ->where('subscription_id', $row->subscription_id)
                    ->update(array_merge($update, [
                        'updated_at' => now(),
                    ]));
            }
        }
    }
}
