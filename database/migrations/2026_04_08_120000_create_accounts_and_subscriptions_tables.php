<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAccountsAndSubscriptionsTables extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('account_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['account_id', 'user_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('plan_code')->default('basic'); // basic|pro|premium
            $table->string('status')->default('inactive'); // active|trialing|past_due|canceled|inactive
            $table->timestamp('current_period_end_at')->nullable();
            $table->timestamps();
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->nullOnDelete();
        });

        // Backfill legacy installs: crear cuenta por defecto y asignar todos los restaurantes a esa cuenta.
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Cuenta por defecto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('restaurants')->whereNull('account_id')->update(['account_id' => $accountId]);
    }

    public function down()
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('account_user');
        Schema::dropIfExists('accounts');
    }
}

