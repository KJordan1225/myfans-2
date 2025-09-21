<?php
// database/migrations/2025_01_01_020000_upgrade_user_profiles_to_v2_constraints_subset.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            /**
             * Modify existing columns to match migration2
             */
            // display_name -> nullable + max length 100
            $table->string('display_name', 100)->nullable()->change();

            // avatar/banner: explicitly 255 (safe; default is 255 but we set it explicitly)
            $table->string('avatar', 255)->nullable()->change();
            $table->string('banner', 255)->nullable()->change();

            // website stays 255 (already default) — no change needed

            // twitter/instagram -> length 100
            $table->string('twitter', 100)->nullable()->change();
            $table->string('instagram', 100)->nullable()->change();

            // balance -> widen precision from (10,2) to (12,2)
            $table->decimal('balance', 12, 2)->default(0)->change();

            // stripe_id -> length 191 (NO index per instruction)
            $table->string('stripe_id', 191)->nullable()->change();

            /**
             * Add new columns introduced in migration2
             * (EXCLUDING processing_paid per instruction)
             */
            // Stripe identifiers
            $table->string('stripe_customer_id', 191)->nullable()->after('stripe_id');
            $table->index('stripe_customer_id'); // allowed

            $table->string('stripe_account_id', 191)->nullable()->after('stripe_customer_id');
            $table->unique('stripe_account_id'); // one Connect account per profile

            // Stripe account status flags
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_payouts_enabled');

            // Currency & requirements
            $table->string('stripe_default_currency', 3)->nullable()->after('stripe_details_submitted');
            $table->json('stripe_requirements')->nullable()->after('stripe_default_currency');

            /**
             * DO NOT add:
             * - processing_paid (skipped)
             * - any index on is_creator (skipped)
             * - index on stripe_id (skipped)
             * - composite index on [is_creator, stripe_account_id] (skipped because it includes is_creator)
             */
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            /**
             * Drop indexes we added (use column arrays so Laravel resolves names)
             */
            $table->dropIndex(['stripe_customer_id']);
            $table->dropUnique(['stripe_account_id']);

            /**
             * Drop columns added in this upgrade
             */
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_account_id',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_details_submitted',
                'stripe_default_currency',
                'stripe_requirements',
            ]);

            /**
             * Revert modified columns back to migration1 shapes
             */
            // display_name back to required (non-nullable) & default length
            $table->string('display_name')->nullable(false)->change();

            // twitter/instagram back to default 255
            $table->string('twitter', 255)->nullable()->change();
            $table->string('instagram', 255)->nullable()->change();

            // balance back to 10,2
            $table->decimal('balance', 10, 2)->default(0)->change();

            // stripe_id back to default length (no index)
            $table->string('stripe_id')->nullable()->change();

            // avatar/banner remain 255 — same as original defaults, no revert needed
        });
    }
};
