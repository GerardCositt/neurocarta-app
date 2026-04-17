<?php

namespace App\Models;

use App\Models\Scopes\RestaurantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new RestaurantScope());
    }

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'action',
        'credits',
        'status',
        'product_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
