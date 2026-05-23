<?php

namespace App\Models;

use App\Models\Scopes\RestaurantScope;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasTranslations;

    protected static function booted(): void
    {
        static::addGlobalScope(new RestaurantScope());
    }

    protected array $translatable = ['name'];

    protected $fillable = [
        'name',
        'hidden',
        'order',
        'icon',
        'restaurant_id',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class)->orderBy('order');
    }

    public function scopeVisible($query)
    {
        return $query->where('hidden', false)->orderBy('order');
    }
}
