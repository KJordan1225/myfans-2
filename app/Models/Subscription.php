<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'price',
        'interval',
        'user_id',
        'stripe_subscription_id',  // sub_***
        'stripe_account_id',       // acct_*** (null if platform-managed)
        'status',                  // active, canceled, past_due, etc.
        'cancel_at_period_end',    // bool
        'canceled_at',             // timestamp
    ];

    /**
     * The creator who owns this subscription (exactly one).
     * (Subscriptions can only have one user as a creator.)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Users who are subscribed to this subscription (many).
     * (Subscriptions can have many users as subscribers.)
     */
    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'subscription_user')
            ->withPivot(['starts_at',
							'ends_at',
							'status',
							'is_active',
							'provider',
							'provider_subscription_id',
							'price_snapshot'])
            ->withTimestamps();
    }

    /** Convenience scope */
    public function activeSubscribers()
    {
        return $this->subscribers()
			->wherePivot('is_active', true)
			->wherePivot('status', 'active');
    }

}
