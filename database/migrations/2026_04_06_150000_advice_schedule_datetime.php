<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estado final: starts_at / ends_at (datetime). Migra datos desde start_date/end_date y elimina columnas puente.
 * down() reconstruye el esquema intermedio para rollback en orden inverso.
 */
return new class extends Migration
{
    /**
     * SQLite: dropColumn vía Schema usa Doctrine DBAL (conflictos con Carbon en este proyecto).
     * SQLite 3.35+ soporta DROP COLUMN nativo.
     *
     * @param  string[]  $columns
     */
    private function dropColumnsCompat(string $table, array $columns): void
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach ($columns as $column) {
                DB::statement('ALTER TABLE "'.$table.'" DROP COLUMN "'.$column.'"');
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns) {
            $blueprint->dropColumn($columns);
        });
    }

    public function up(): void
    {
        if (! Schema::hasColumn('advice', 'starts_at')) {
            Schema::table('advice', function (Blueprint $table) {
                $table->dateTime('starts_at')->nullable()->after('status');
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            });
        }

        if (Schema::hasColumn('advice', 'start_date')) {
            DB::table('advice')->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $starts = null;
                    $ends = null;
                    if (! empty($row->start_date)) {
                        $starts = Carbon::parse($row->start_date)->startOfDay();
                    }
                    if (! empty($row->end_date)) {
                        $ends = Carbon::parse($row->end_date)->endOfDay();
                    }
                    DB::table('advice')->where('id', $row->id)->update([
                        'starts_at' => $starts,
                        'ends_at' => $ends,
                    ]);
                }
            });

            $this->dropColumnsCompat('advice', ['start_date', 'end_date']);
        }

        if (Schema::hasColumn('advice', 'force_active')) {
            $this->dropColumnsCompat('advice', ['force_active']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('advice', 'start_date')) {
            Schema::table('advice', function (Blueprint $table) {
                $table->date('start_date')->nullable()->after('status');
                $table->date('end_date')->nullable()->after('start_date');
            });
        }

        if (Schema::hasColumn('advice', 'starts_at')) {
            DB::table('advice')->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $sd = null;
                    $ed = null;
                    if (! empty($row->starts_at)) {
                        $sd = Carbon::parse($row->starts_at)->toDateString();
                    }
                    if (! empty($row->ends_at)) {
                        $ed = Carbon::parse($row->ends_at)->toDateString();
                    }
                    DB::table('advice')->where('id', $row->id)->update([
                        'start_date' => $sd,
                        'end_date' => $ed,
                    ]);
                }
            });

            $this->dropColumnsCompat('advice', ['starts_at', 'ends_at']);
        }

        if (! Schema::hasColumn('advice', 'force_active')) {
            Schema::table('advice', function (Blueprint $table) {
                $table->boolean('force_active')->default(false)->after('status');
            });
        }
    }
};
