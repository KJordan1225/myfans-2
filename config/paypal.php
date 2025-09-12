<?php

return [
  'client_id' => env('PAYPAL_CLIENT_ID'),
  'secret'    => env('PAYPAL_SECRET'),
  'base'      => rtrim(env('PAYPAL_BASE', 'https://api-m.sandbox.paypal.com'), '/'),
  'webhook_id'=> env('PAYPAL_WEBHOOK_ID'),
  'payouts_sender_email' => env('PAYPAL_PAYOUTS_SENDER_EMAIL'),
];
