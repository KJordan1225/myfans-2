<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('checkout_sessions', function (Blueprint $t) {
            $t->id();
            $t->string('session_id')->unique();
            $t->foreignId('follower_id')->constrained('users');
            $t->foreignId('creator_id')->constrained('users');
            $t->foreignId('plan_id')->constrained('creator_plans');
            $t->string('stripe_account_id');   // connected account id
            $t->string('status')->default('pending'); // pending|completed|canceled
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('checkout_sessions');
    }
};
