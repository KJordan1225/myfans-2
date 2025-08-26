<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    // Mass-assignable fields (match your migration)
    protected $fillable = [
        'buyer_id',
        'creator_id',
        'payment_intent_id',
        'amount_cents',
        'currency',
        'status',
        'fulfilled_at',
        'meta',
    ];

    // Casts for convenient usage
    protected $casts = [
        'meta'         => 'array',
        'fulfilled_at' => 'datetime',
    ];

    /**
     * The user who bought the item/content.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * The creator (seller) who receives the funds.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // (Optional) quick status helpers
    public function markSucceeded(array $extraMeta = []): void
    {
        $this->update([
            'status'       => 'succeeded',
            'fulfilled_at' => now(),
            'meta'         => array_merge($this->meta ?? [], $extraMeta),
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}

