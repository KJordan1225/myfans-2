<?php

namespace App\Services;

use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class BalanceService {
  public function availableForCreator(int $creatorId, string $currency='USD'): float {
    $credits = LedgerEntry::where('creator_id',$creatorId)
      ->where('type','credit')
      ->where('currency',$currency)
      ->where(function($q){ $q->whereNull('available_on')->orWhere('available_on','<=',now()); })
      ->sum('amount');

    $debits = LedgerEntry::where('creator_id',$creatorId)
      ->where('type','debit')
      ->where('currency',$currency)
      ->sum('amount');

    return round($credits - $debits, 2);
  }
}
