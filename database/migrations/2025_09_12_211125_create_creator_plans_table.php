<?php
// database/migrations/2025_01_01_000500_create_creator_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('creator_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $t->string('name', 120);
            $t->unsignedInteger('price_cents');          // whole cents
            $t->string('currency', 3)->default('usd');
            $t->enum('interval', ['day','week','month','year'])->default('month');
            $t->boolean('is_active')->default(true);

            $t->string('stripe_product_id', 191)->nullable()->index();
            $t->string('stripe_price_id', 191)->nullable()->index();

            $t->timestamps();

            // Optional uniqueness by name per creator
            $t->unique(['creator_id','name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_plans');
    }
};
