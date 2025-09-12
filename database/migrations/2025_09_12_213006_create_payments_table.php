<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('payments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('subscription_id')->constrained()->cascadeOnDelete();
      $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
      $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // subscriber
      $t->string('provider')->default('paypal');
      $t->string('provider_txn_id')->index(); // sale id / capture id
      $t->string('currency', 3);
      $t->decimal('amount', 10, 2);
      $t->string('status')->index(); // COMPLETED, FAILED, etc.
      $t->timestamp('paid_at')->nullable();
      $t->json('raw')->nullable();
      $t->timestamps();
      $t->unique(['provider','provider_txn_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('payments'); }
};
