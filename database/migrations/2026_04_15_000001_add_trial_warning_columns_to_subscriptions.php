<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('trial_warning_day5_sent_at')->nullable()->after('current_period_end_at');
            $table->timestamp('trial_warning_day7_sent_at')->nullable()->after('trial_warning_day5_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['trial_warning_day5_sent_at', 'trial_warning_day7_sent_at']);
        });
    }
};
