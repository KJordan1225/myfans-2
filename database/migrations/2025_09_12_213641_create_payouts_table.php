<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('payouts', function (Blueprint $t) {
      $t->id();
      $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
      $t->string('status')->index(); // queued|processing|paid|failed
      $t->string('currency', 3)->default('USD');
      $t->decimal('amount', 10, 2);
      $t->string('paypal_batch_id')->nullable()->index();
      $t->string('paypal_item_id')->nullable()->index();
      $t->string('recipient')->nullable(); // PayPal email
      $t->timestamp('attempted_at')->nullable();
      $t->json('raw')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('payouts'); }
};
