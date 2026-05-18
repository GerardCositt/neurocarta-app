<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filtros LIKE insensibles a mayúsculas en panel admin.
 * PostgreSQL: ILIKE (Unicode correcto). Otros drivers: LOWER(columna) LIKE patrón en minúsculas.
 */
final class CaseInsensitiveLike
{
    public static function applyWhere(Builder $query, string $qualifiedColumn, string $needle): void
    {
        $needle = trim($needle);
        if ($needle === '') {
            return;
        }

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $query->whereRaw($qualifiedColumn.' ILIKE ?', ['%'.$needle.'%']);

            return;
        }

        $pattern = '%'.mb_strtolower($needle, 'UTF-8').'%';
        $query->whereRaw('LOWER('.$qualifiedColumn.') LIKE ?', [$pattern]);
    }

    public static function applyOrWhere(Builder $query, string $qualifiedColumn, string $needle): void
    {
        $needle = trim($needle);
        if ($needle === '') {
            return;
        }

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $query->orWhereRaw($qualifiedColumn.' ILIKE ?', ['%'.$needle.'%']);

            return;
        }

        $pattern = '%'.mb_strtolower($needle, 'UTF-8').'%';
        $query->orWhereRaw('LOWER('.$qualifiedColumn.') LIKE ?', [$pattern]);
    }
}
