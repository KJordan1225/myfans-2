<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_user', function (Blueprint $table) {
            $table->id();

            // the subscriber (a user)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // the subscription they purchased (owned by some creator)
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();

            // lifecycle & billing snapshots (optional but recommended)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('active'); // active|past_due|canceled|expired
            $table->boolean('is_active')->default(true);
            $table->string('provider')->nullable();                 // 'stripe'
            $table->string('provider_subscription_id')->nullable(); // Stripe sub id at time of purchase
            $table->decimal('price_snapshot', 8, 2)->nullable();    // price at purchase

            $table->timestamps();

            $table->unique(['user_id', 'subscription_id']); // prevent duplicates
            $table->index(['subscription_id', 'is_active']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_user');
    }
};
