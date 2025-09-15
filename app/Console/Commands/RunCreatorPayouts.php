<?php
namespace App\Console\Commands;

use App\Models\Payout;
use App\Models\User;
use App\Services\{BalanceService, PayPalClient};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunCreatorPayouts extends Command
{
  protected $signature = 'payouts:run {--min=5.00} {--currency=USD}';
  protected $description = 'Batch PayPal payouts to creators for available balances';

  public function handle(PayPalClient $pp, BalanceService $bal): int
  {
    $min = (float) $this->option('min');
    $currency = $this->option('currency');

    $creatorIds = \App\Models\LedgerEntry::query()->select('creator_id')->distinct()->pluck('creator_id');

    foreach ($creatorIds as $creatorId) {
      $available = $bal->availableForCreator($creatorId, $currency);
      if ($available < $min) continue;

      $creator = User::find($creatorId);
      $recipient = optional($creator->profile)->paypal_email ?? $creator->email;

      DB::transaction(function () use ($pp, $creatorId, $recipient, $available, $currency) {
        $payout = Payout::create([
          'creator_id'=>$creatorId,
          'status'=>'queued',
          'currency'=>$currency,
          'amount'=>$available,
          'recipient'=>$recipient,
        ]);

        $resp = $pp->createPayout($recipient, number_format($available, 2, '.', ''), $currency, "Payout for creator #{$creatorId}");
        $batchId = $resp['batch_header']['payout_batch_id'] ?? null;

        $payout->update([
          'status'=>'processing',
          'paypal_batch_id'=>$batchId,
          'attempted_at'=>now(),
          'raw'=>$resp,
        ]);

        // Debit the ledger for the payout amount
        \App\Models\LedgerEntry::create([
          'creator_id'=>$creatorId,
          'type'=>'debit',
          'currency'=>$currency,
          'amount'=>$available,
          'source_type'=>'payout',
          'source_id'=>$payout->id,
          'memo'=>'Payout sent',
        ]);
      });
    }

    $this->info('Payouts queued.');
    return self::SUCCESS;
  }
}
