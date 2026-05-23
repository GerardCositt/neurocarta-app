<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantApiKey extends Model
{
    protected $fillable = ['restaurant_id', 'key_hash', 'key_prefix', 'last_used_at', 'revoked_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /** Generate a new key, persist it, and return the raw key (shown once). */
    public static function generate(int $restaurantId): array
    {
        $raw    = 'nc_live_' . bin2hex(random_bytes(24));
        $hash   = hash('sha256', $raw);
        $prefix = substr($raw, 0, 16) . '...';

        $model = static::create([
            'restaurant_id' => $restaurantId,
            'key_hash'      => $hash,
            'key_prefix'    => $prefix,
        ]);

        return ['model' => $model, 'raw_key' => $raw];
    }

    /** Find an active key by its raw value. */
    public static function findByRawKey(string $raw): ?static
    {
        $hash = hash('sha256', $raw);

        return static::where('key_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
    }
}
