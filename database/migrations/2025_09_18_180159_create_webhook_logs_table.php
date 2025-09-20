<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('webhook_logs', function (Blueprint $t) {
            $t->id();
            $t->string('provider')->index();                 // 'stripe'
            $t->string('event_id')->unique();                // Stripe event id (idempotency)
            $t->string('event_type')->index();
            $t->string('account_id')->nullable()->index();   // connected acct (acct_xxx)
            $t->unsignedSmallInteger('http_status')->default(0);
            $t->uuid('rid')->nullable();                     // your correlation id (client_reference_id)
            $t->json('payload');                             // raw event
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('webhook_logs'); }
};
