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

            // the creator (a user) who owns this subscription plan
            $table->foreignId('creator_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('title')->default('Creator Plan');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2); // e.g. monthly price
            $table->string('interval')->default('month'); // month, year (optional)
            $table->timestamps();
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
