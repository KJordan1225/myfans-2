<?php
// app/Models/Subscription.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Subscription extends Model
{
    protected $fillable = [
        'user_id','creator_id','creator_plan_id',
        'stripe_account_id','stripe_customer_id','stripe_subscription_id',
        'status','cancel_at_period_end','current_period_end',
    ];

    protected $casts = [
        'cancel_at_period_end' => 'boolean',
        'current_period_end'   => 'datetime',
    ];

    public function follower() { return $this->belongsTo(User::class, 'user_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'creator_id'); }
    public function plan()     { return $this->belongsTo(CreatorPlan::class, 'creator_plan_id'); }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // adjust FK if different
    }


    public function isActive(): bool
    {
        return in_array($this->status, ['trialing','active'], true) && ! $this->cancel_at_period_end;
    }
}
