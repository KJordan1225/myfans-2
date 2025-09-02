<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id','name','stripe_product_id','stripe_price_id','amount','currency','interval','platform_fee_percent','active',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

}
