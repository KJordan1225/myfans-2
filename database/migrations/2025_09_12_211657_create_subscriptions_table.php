<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Fan (subscriber) and Creator
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();

            // Provider + IDs (PayPal: provider_subscription_id = "I-XXXX", provider_plan_id = "P-XXXX")
            $table->string('provider', 32)->default('paypal')->index();
            $table->string('provider_subscription_id', 100)->unique();
            $table->string('provider_plan_id', 100)->index();

            // Status examples (PayPal): APPROVAL_PENDING, APPROVED, ACTIVE, SUSPENDED, CANCELLED, EXPIRED, PAST_DUE
            $table->string('status', 32)->index();

            // Timeline
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Raw provider payloads, plan details, etc.
            $table->json('meta')->nullable();

            $table->timestamps();

            // Helpful compound index for dashboards/queries
            $table->index(['creator_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};


