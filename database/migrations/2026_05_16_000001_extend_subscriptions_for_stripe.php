<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_interval')->default('monthly')->after('plan_code');
            $table->timestamp('current_period_start_at')->nullable()->after('billing_interval');
            $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            $table->timestamp('grace_period_ends_at')->nullable()->after('current_period_end_at');
            $table->timestamp('canceled_at')->nullable()->after('grace_period_ends_at');
        });

        // doctrine/dbal is not a direct dependency on Laravel 8; use raw SQL.
        // SQLite (used in tests) does not support ALTER COLUMN — only needed on PostgreSQL.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE subscriptions ALTER COLUMN plan_code SET DEFAULT 'basico'");
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'billing_interval',
                'current_period_start_at',
                'stripe_price_id',
                'grace_period_ends_at',
                'canceled_at',
            ]);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE subscriptions ALTER COLUMN plan_code SET DEFAULT 'basic'");
        }
    }
};
