<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatorPrices extends Model
{
    protected $table = 'creator_prices';

    protected $fillable = [
        'creator_id',
        'stripe_price_id',
        'display_name',
        'amount',
    ];
}
