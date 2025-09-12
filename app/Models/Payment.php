<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model {
  protected $fillable = ['subscription_id','creator_id','user_id','provider','provider_txn_id','currency','amount','status','paid_at','raw'];
  protected $casts = ['raw'=>'array','paid_at'=>'datetime'];
  public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}