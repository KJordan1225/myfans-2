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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // Who bought it
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();

            // Which creator (seller) it belongs to
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();

            // Stripe PaymentIntent id (unique to enforce idempotency)
            $table->string('payment_intent_id')->unique();

            // Payment info
            $table->integer('amount_cents'); // store amounts as integers (in cents)
            $table->string('currency', 10)->default('usd');

            // Status of the purchase
            $table->enum('status', ['pending','succeeded','failed'])->default('pending');

            // When fulfillment (unlocking access, delivering product) was done
            $table->timestamp('fulfilled_at')->nullable();

            // Extra data from Stripe (charges, fees, etc.)
            $table->json('meta')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
