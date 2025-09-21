<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSession extends Model
{
    protected $fillable = [
        'session_id','follower_id','creator_id','plan_id','stripe_account_id','status',
    ];
}

