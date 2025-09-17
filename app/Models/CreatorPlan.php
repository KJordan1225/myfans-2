<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CreatorPlan extends Model {
  protected $fillable = [
    'creator_id','name','currency','amount','interval_unit','interval_count',
    'active','paypal_product_id','paypal_plan_id'
  ];
  public function creator(){ return $this->belongsTo(User::class,'creator_id'); }
}
