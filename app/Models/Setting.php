<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'restaurant_id',
    ];

    public static function get(string $key, $default = null, ?int $restaurantId = null)
    {
        $query = self::query()->where('key', $key);
        if ($restaurantId !== null) {
            $query->where('restaurant_id', $restaurantId);
        } else {
            // Ajustes globales: una fila por clave con restaurant_id NULL (no «el primero que coincida la key»)
            $query->whereNull('restaurant_id');
        }
        $row = $query->first();
        if (! $row) {
            return $default;
        }
        return $row->value;
    }

    public static function getBool(string $key, bool $default = false, ?int $restaurantId = null): bool
    {
        $v = self::get($key, $default ? '1' : '0', $restaurantId);
        if (is_bool($v)) return $v;
        if ($v === null) return $default;
        $s = strtolower(trim((string) $v));
        if ($s === '1' || $s === 'true' || $s === 'yes' || $s === 'on') return true;
        if ($s === '0' || $s === 'false' || $s === 'no' || $s === 'off') return false;
        return $default;
    }

    public static function put(string $key, $value, ?int $restaurantId = null): void
    {
        self::query()->updateOrCreate(
            [
                'key' => $key,
                'restaurant_id' => $restaurantId,
            ],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'restaurant_id' => $restaurantId,
            ]
        );
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
