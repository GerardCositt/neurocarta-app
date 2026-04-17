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
        'active',
        'order',
        'icon',
        'restaurant_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class)->orderBy('order');
    }

    // categorías visibles (active=0 en la lógica actual)
    public function scopeVisible($query)
    {
        return $query->where('active', false)->orderBy('order');
    }
}
