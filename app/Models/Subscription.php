<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_id','creator_id','creator_plan_id',
        'stripe_subscription_id','stripe_customer_id','stripe_account_id','status',
        'current_period_start','current_period_end','cancel_at_period_end',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'cancel_at_period_end' => 'boolean',
    ];

    public function subscriber(): BelongsTo { return $this->belongsTo(User::class,'subscriber_id'); }
    public function creator(){ return $this->belongsTo(User::class,'creator_id'); }
    public function plan(){ return $this->belongsTo(CreatorPlan::class,'provider_plan_id','paypal_plan_id'); }
    public function user(){ return $this->belongsTo(User::class,'user_id'); }

    /** Active = status ACTIVE and not ended (ends_at null or future) */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            });
    }



}
