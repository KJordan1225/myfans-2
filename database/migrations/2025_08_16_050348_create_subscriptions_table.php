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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_plan_id')->constrained('creator_plans')->cascadeOnDelete();

            $table->string('stripe_subscription_id')->index();
            $table->string('stripe_customer_id');
            $table->string('stripe_account_id'); // destination connected account
            $table->string('status'); // active, trialing, past_due, canceled, incomplete, paused
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            $table->timestamps();
            $table->unique(['subscriber_id','creator_id'], 'unique_subscriber_creator');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
