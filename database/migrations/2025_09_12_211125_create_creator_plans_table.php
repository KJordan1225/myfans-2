<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('creator_plans', function (Blueprint $t) {
      $t->id();
      $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
      $t->string('name');
      $t->string('currency', 3)->default('USD');
      $t->decimal('amount', 10, 2);
      $t->string('interval_unit')->default('MONTH'); // DAY, WEEK, MONTH, YEAR
      $t->unsignedSmallInteger('interval_count')->default(1);
      $t->boolean('active')->default(true);

      // PayPal artifacts
      $t->string('paypal_product_id')->nullable();
      $t->string('paypal_plan_id')->nullable()->unique();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('creator_plans'); }
};
