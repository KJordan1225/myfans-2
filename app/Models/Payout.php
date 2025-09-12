<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model {
  protected $fillable = ['creator_id','status','currency','amount','paypal_batch_id','paypal_item_id','recipient','attempted_at','raw'];
  protected $casts = ['attempted_at'=>'datetime','raw'=>'array'];
}

