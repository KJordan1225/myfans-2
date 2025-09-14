<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Payment;
use App\Models\LedgerEntry;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function __construct(private PayPalClient $paypal) {}

    public function handle(Request $request)
    {
        // 1) Verify signature (mandatory)
        if (!$this->verify($request)) {
            Log::warning('PayPal webhook verification failed', ['h' => $this->safeHeaders($request)]);
            return response('invalid', 403);
        }

        // 2) Route on event type
        $event    = (string) $request->input('event_type');
        $resource = (array)  $request->input('resource', []);

        // subscription id can appear in different fields depending on event
        $subscriptionId = $resource['id']
            ?? $resource['billing_agreement_id']
            ?? $resource['subscription_id']
            ?? null;

        try {
            return match ($event) {
                'BILLING.SUBSCRIPTION.ACTIVATED'         => $this->mark($subscriptionId, 'ACTIVE',    $resource),
                'BILLING.SUBSCRIPTION.CANCELLED'         => $this->mark($subscriptionId, 'CANCELLED', $resource, true),
                'BILLING.SUBSCRIPTION.SUSPENDED'         => $this->mark($subscriptionId, 'SUSPENDED', $resource),
                'BILLING.SUBSCRIPTION.EXPIRED'           => $this->mark($subscriptionId, 'EXPIRED',   $resource, true),
                'BILLING.SUBSCRIPTION.UPDATED'           => $this->updateMeta($subscriptionId, $resource),
                'BILLING.SUBSCRIPTION.PAYMENT.FAILED'    => $this->mark($subscriptionId, 'PAST_DUE',  $resource),
                'PAYMENT.SALE.COMPLETED'                 => $this->recordPayment($subscriptionId, $resource),
                default                                   => response('ok'), // ignore unhandled events
            };
        } catch (\Throwable $e) {
            Log::error('PayPal webhook exception: '.$e->getMessage(), [
                'event' => $event,
                'sub'   => $subscriptionId,
            ]);
            // Acknowledge to prevent endless retries; log for follow-up.
            return response('ok');
        }
    }

    /* ---------- Helpers ---------- */

    private function verify(Request $request): bool
    {
        $headers = [
            'paypal-transmission-id'   => $request->header('paypal-transmission-id'),
            'paypal-transmission-time' => $request->header('paypal-transmission-time'),
            'paypal-cert-url'          => $request->header('paypal-cert-url'),
            'paypal-auth-algo'         => $request->header('paypal-auth-algo'),
            'paypal-transmission-sig'  => $request->header('paypal-transmission-sig'),
        ];

        return $this->paypal->verifyWebhook($headers, $request->getContent());
    }

    private function mark(?string $providerId, string $status, array $resource, bool $setEnd = false)
    {
        if (!$providerId) return response('ok');

        $payload = ['status' => $status, 'meta' => $resource];
        if ($setEnd) {
            $payload['ends_at'] = now();
        }

        Subscription::where('provider', 'paypal')
            ->where('provider_subscription_id', $providerId)
            ->update($payload);

        return response('ok');
    }

    private function updateMeta(?string $providerId, array $resource)
    {
        if (!$providerId) return response('ok');

        Subscription::where('provider', 'paypal')
            ->where('provider_subscription_id', $providerId)
            ->update(['meta' => $resource]);

        return response('ok');
    }

    private function recordPayment(?string $subscriptionId, array $resource)
    {
        if (!$subscriptionId) return response('ok');

        $sub = Subscription::where('provider', 'paypal')
            ->where('provider_subscription_id', $subscriptionId)
            ->first();

        if (!$sub) return response('ok');

        [$amount, $currency] = $this->extractAmountCurrency($resource);
        $txnId = $resource['id'] ?? ($resource['transaction_info']['transaction_id'] ?? null);

        if (!$txnId) {
            // Fall back to a stable synthetic key if PayPal ever omits id
            $txnId = 'pp_'.md5(json_encode($resource));
        }

        // Idempotent write (unique on provider_txn_id)
        $payment = Payment::firstOrCreate(
            ['provider' => 'paypal', 'provider_txn_id' => $txnId],
            [
                'subscription_id' => $sub->id,
                'creator_id'      => $sub->creator_id,
                'user_id'         => $sub->user_id,
                'currency'        => $currency ?? 'USD',
                'amount'          => $amount  ?? 0,
                'status'          => 'COMPLETED',
                'paid_at'         => now(),
                'raw'             => $resource,
            ]
        );

        if ($payment->wasRecentlyCreated) {
            // Platform fee & clearing window
            $feeBps     = (int) config('app.platform_fee_bps', env('PLATFORM_FEE_BPS', 1000)); // 1000 = 10%
            $fee        = round($payment->amount * $feeBps / 10000, 2);
            $net        = round($payment->amount - $fee, 2);
            $available  = now()->addDays((int) env('CLEARING_DAYS', 7));

            // Credit (net) to creator, available after clearing window
            LedgerEntry::create([
                'creator_id'   => $sub->creator_id,
                'type'         => 'credit',
                'currency'     => $payment->currency,
                'amount'       => $net,
                'source_type'  => 'payment',
                'source_id'    => $payment->id,
                'available_on' => $available,
                'memo'         => 'Subscription payment (net)',
                'meta'         => ['gross' => $payment->amount, 'fee' => $fee],
            ]);

            // Fee debit (transparent)
            LedgerEntry::create([
                'creator_id'  => $sub->creator_id,
                'type'        => 'debit',
                'currency'    => $payment->currency,
                'amount'      => $fee,
                'source_type' => 'fee',
                'source_id'   => $payment->id,
                'memo'        => 'Platform fee',
                'meta'        => ['bps' => $feeBps],
            ]);

            // Keep sub active on successful renewal
            $sub->update(['status' => 'ACTIVE']);
        }

        return response('ok');
    }

    private function extractAmountCurrency(array $resource): array
    {
        // PayPal payloads vary across APIs. Try common shapes.
        $amount   = null;
        $currency = null;

        // Classic sale format
        if (isset($resource['amount']['total'])) {
            $amount   = (float) $resource['amount']['total'];
            $currency = $resource['amount']['currency'] ?? null;
        }
        // v2 capture-like format
        elseif (isset($resource['amount']['value'])) {
            $amount   = (float) $resource['amount']['value'];
            $currency = $resource['amount']['currency_code'] ?? null;
        }
        // Transaction summary format
        elseif (isset($resource['transaction_info']['transaction_amount'])) {
            $amount   = (float) ($resource['transaction_info']['transaction_amount']['value'] ?? 0);
            $currency = $resource['transaction_info']['transaction_amount']['currency_code'] ?? null;
        }

        return [$amount, $currency];
    }

    private function safeHeaders(Request $request): array
    {
        // Don’t log sensitive header contents
        return [
            'paypal-transmission-id'   => (bool) $request->header('paypal-transmission-id'),
            'paypal-transmission-time' => (bool) $request->header('paypal-transmission-time'),
            'paypal-cert-url'          => (bool) $request->header('paypal-cert-url'),
            'paypal-auth-algo'         => (bool) $request->header('paypal-auth-algo'),
            'paypal-transmission-sig'  => (bool) $request->header('paypal-transmission-sig'),
        ];
    }
}

