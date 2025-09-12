<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ledger_entries', function (Blueprint $t) {
      $t->id();
      $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
      $t->enum('type', ['credit','debit']);
      $t->string('currency', 3)->default('USD');
      $t->decimal('amount', 10, 2); // positive number
      $t->string('source_type');    // payment|payout|fee|adjustment
      $t->unsignedBigInteger('source_id')->nullable(); // FK-ish pointer
      $t->timestamp('available_on')->nullable(); // when credit becomes withdrawable
      $t->string('memo')->nullable();
      $t->json('meta')->nullable();
      $t->timestamps();
      $t->index(['creator_id','type','available_on']);
    });
  }
  public function down(): void { Schema::dropIfExists('ledger_entries'); }
};
