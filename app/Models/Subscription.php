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
    ];

    
}
