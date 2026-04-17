<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar que no existen duplicados antes de aplicar:
        // SELECT user_id, COUNT(*) FROM account_user GROUP BY user_id HAVING COUNT(*) > 1;
        Schema::table('account_user', function (Blueprint $table) {
            $table->unique('user_id', 'account_user_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('account_user', function (Blueprint $table) {
            $table->dropUnique('account_user_user_id_unique');
        });
    }
};
