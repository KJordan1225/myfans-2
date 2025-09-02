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
        Schema::create('creator_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('stripe_product_id');
            $table->string('stripe_price_id')->index();
            $table->unsignedInteger('amount'); // in cents
            $table->string('currency')->default('usd');
            $table->enum('interval', ['day','week','month','year'])->default('month');
            $table->decimal('platform_fee_percent', 5, 2, true)->default(15.00);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_plans');
    }
};
