<?php
// app/Models/CreatorPlan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorPlan extends Model
{
    protected $fillable = [
        'creator_id','name','price_cents','currency','interval',
        'is_active','stripe_product_id','stripe_price_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getPriceForHumansAttribute(): string
    {
        return sprintf('%s %.2f / %s', strtoupper($this->currency), $this->price_cents / 100, $this->interval);
    }
}
