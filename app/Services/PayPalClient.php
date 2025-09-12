<?php

namespace App\Services;


class PayPalClient extends PayPalBase {

    public function createPayout(string $recipientEmail, string $amount, string $currency='USD', ?string $note=null): array
    {
    $senderBatchId = 'batch_'.now()->timestamp.'_'.bin2hex(random_bytes(4));
    $res = $this->http()->post('v1/payments/payouts', [
        'headers' => $this->headers(),
        'json' => [
        'sender_batch_header' => [
            'sender_batch_id' => $senderBatchId,
            'email_subject' => 'You have a payout',
            'email_message' => 'You have received a payout from our platform.',
        ],
        'items' => [[
            'recipient_type' => 'EMAIL',
            'amount' => ['value' => $amount, 'currency' => strtoupper($currency)],
            'receiver' => $recipientEmail,
            'note' => $note ?? 'Creator earnings payout',
            'sender_item_id' => 'item_'.bin2hex(random_bytes(4))
        ]],
        ],
    ]);
    return json_decode((string)$res->getBody(), true);
    }

    public function getPayoutBatch(string $batchId): array
    {
    $res = $this->http()->get("v1/payments/payouts/{$batchId}", ['headers'=>$this->headers()]);
    return json_decode((string)$res->getBody(), true);
    }
}