<?php

namespace App\Models;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_notes',
        'status',
        'restaurant_id',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING   => 'Pendiente',
            self::STATUS_PREPARING => 'En preparación',
            self::STATUS_READY     => 'Listo',
            self::STATUS_DONE      => 'Entregado',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public static function allowedStatuses(): array
    {
        return array_keys(self::statusLabels());
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function labelForStatus(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
