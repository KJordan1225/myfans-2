<?php
// database/migrations/2025_01_01_060000_create_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();

            // Who is following whom
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();        // follower
            $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();     // creator
            $t->foreignId('creator_plan_id')->constrained('creator_plans')->cascadeOnDelete();

            // Stripe objects (connected account)
            $t->string('stripe_account_id', 191)->nullable()->index();   // creator's connected acct
            $t->string('stripe_customer_id', 191)->nullable()->index();  // customer in connected account
            $t->string('stripe_subscription_id', 191)->nullable()->unique();

            // Status tracking
            $t->string('status', 50)->default('incomplete'); // trialing, active, past_due, canceled, unpaid, incomplete, incomplete_expired
            $t->boolean('cancel_at_period_end')->default(false);
            $t->timestamp('current_period_end')->nullable();

            $t->timestamps();

            // A follower can only have one active subscription for a given plan (optional)
            $t->unique(['user_id', 'creator_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
