<?php
namespace App\Services;

use GuzzleHttp\Client;

class PayPalClient
{
  public function http(): Client {
    return new Client(['base_uri' => config('paypal.base').'/', 'timeout' => 20]);
  }

  public function token(): string {
    $res = $this->http()->post('v1/oauth2/token', [
      'auth' => [config('paypal.client_id'), config('paypal.secret')],
      'form_params' => ['grant_type' => 'client_credentials'],
    ]);
    $data = json_decode((string) $res->getBody(), true);
    return $data['access_token'];
  }

  public function headers(): array {
    return ['Authorization' => 'Bearer '.$this->token(), 'Content-Type' => 'application/json'];
  }

  // Product + Plan for a creator
  public function createProduct(string $name, string $description=''): array {
    $res = $this->http()->post('v1/catalogs/products', [
      'headers' => $this->headers(),
      'json' => ['name'=>$name,'type'=>'SERVICE','description'=>$description],
    ]);
    return json_decode((string) $res->getBody(), true);
  }

  public function createPlan(string $productId, string $nickname, string $currency, string $amount, string $interval='MONTH', int $intervalCount=1): array {
    $res = $this->http()->post('v1/billing/plans', [
      'headers' => $this->headers(),
      'json' => [
        'product_id' => $productId,
        'name' => $nickname,
        'status' => 'ACTIVE',
        'billing_cycles' => [[
          'frequency' => ['interval_unit'=>strtoupper($interval),'interval_count'=>$intervalCount],
          'tenure_type' => 'REGULAR',
          'sequence' => 1,
          'total_cycles' => 0,
          'pricing_scheme' => ['fixed_price' => ['value' => $amount, 'currency_code' => strtoupper($currency)]],
        ]],
        'payment_preferences' => [
          'auto_bill_outstanding' => true,
          'setup_fee_failure_action' => 'CONTINUE',
          'payment_failure_threshold' => 3,
        ],
      ],
    ]);
    return json_decode((string) $res->getBody(), true);
  }

  // Subscriptions
  public function getSubscription(string $subscriptionId): array {
    $res = $this->http()->get("v1/billing/subscriptions/{$subscriptionId}", ['headers'=>$this->headers()]);
    return json_decode((string) $res->getBody(), true);
  }
  public function cancelSubscription(string $subscriptionId, string $reason='user'): void {
    $this->http()->post("v1/billing/subscriptions/{$subscriptionId}/cancel", [
      'headers'=>$this->headers(), 'json'=>['reason'=>$reason],
    ]);
  }

  // Webhook verification
  public function verifyWebhook(array $headers, string $body): bool {
    $res = $this->http()->post('v1/notifications/verify-webhook-signature', [
      'headers'=>$this->headers(),
      'json'=>[
        'transmission_id'   => $headers['paypal-transmission-id'] ?? '',
        'transmission_time' => $headers['paypal-transmission-time'] ?? '',
        'cert_url'          => $headers['paypal-cert-url'] ?? '',
        'auth_algo'         => $headers['paypal-auth-algo'] ?? '',
        'transmission_sig'  => $headers['paypal-transmission-sig'] ?? '',
        'webhook_id'        => config('paypal.webhook_id'),
        'webhook_event'     => json_decode($body, true),
      ],
    ]);
    $data = json_decode((string)$res->getBody(), true);
    return ($data['verification_status'] ?? '') === 'SUCCESS';
  }

  // Payouts
  public function createPayout(string $recipientEmail, string $amount, string $currency='USD', ?string $note=null): array {
    $res = $this->http()->post('v1/payments/payouts', [
      'headers'=>$this->headers(),
      'json'=>[
        'sender_batch_header'=>[
          'sender_batch_id'=>'batch_'.now()->timestamp.'_'.bin2hex(random_bytes(3)),
          'email_subject'=>'You have a payout',
          'email_message'=>'You have received a payout from our platform.',
        ],
        'items'=>[[
          'recipient_type'=>'EMAIL',
          'receiver'=>$recipientEmail,
          'amount'=>['value'=>$amount,'currency'=>strtoupper($currency)],
          'note'=>$note ?? 'Creator earnings payout',
          'sender_item_id'=>'item_'.bin2hex(random_bytes(3)),
        ]],
      ],
    ]);
    return json_decode((string) $res->getBody(), true);
  }

  public function getPayoutBatch(string $batchId): array {
    $res = $this->http()->get("v1/payments/payouts/{$batchId}", ['headers'=>$this->headers()]);
    return json_decode((string)$res->getBody(), true);
  }
}
