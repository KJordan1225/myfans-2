<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model {
  protected $fillable = ['creator_id','type','currency','amount','source_type','source_id','available_on','memo','meta'];
  protected $casts = ['available_on'=>'datetime','meta'=>'array'];
}
