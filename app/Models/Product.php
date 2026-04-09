<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasTranslations;

    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'active',
        'category_id',
        'pairing_id',
        'price',
        'offer_price',
        'offer',
        'offer_badge',
        'offer_start',
        'offer_end',
        'photo',
        'aller',
        'order',
        'featured',
        'recommended',
        'restaurant_id',
    ];

    protected $casts = [
        'offer'        => 'boolean',
        'active'       => 'boolean',
        'featured'     => 'boolean',
        'recommended'  => 'boolean',
        'offer_start' => 'date',
        'offer_end'   => 'date',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pairing()
    {
        return $this->belongsTo(Pairing::class);
    }

    // productos visibles (active=0 en la lógica actual)
    public function scopeVisible($query)
    {
        return $query->where('active', 0);
    }

    // productos con oferta activa ahora
    public function scopeWithActiveOffer($query)
    {
        return $query->where('offer', 1)
            ->where(function ($q) {
                $q->whereNull('offer_start')->orWhere('offer_start', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('offer_end')->orWhere('offer_end', '>=', now());
            });
    }

    /**
     * Orden carta pública: destacados → recomendados → oferta vigente → orden manual.
     * La lógica de oferta coincide con scopeWithActiveOffer / isOfferActive().
     */
    public function scopeOrderForMenu($query)
    {
        $now = now()->format('Y-m-d H:i:s');

        return $query->orderByDesc('featured')
            ->orderByDesc('recommended')
            ->orderByRaw(
                '(CASE WHEN offer = 1 AND (offer_start IS NULL OR offer_start <= ?) AND (offer_end IS NULL OR offer_end >= ?) THEN 1 ELSE 0 END) DESC',
                [$now, $now]
            )
            ->orderBy('order');
    }

    public function isOfferActive(): bool
    {
        if (!$this->offer) return false;
        if ($this->offer_start && $this->offer_start->isFuture()) return false;
        if ($this->offer_end && $this->offer_end->isPast()) return false;
        return true;
    }

    public function allergens()
    {
        return $this->belongsToMany(Allergen::class);
    }

    /**
     * Alérgenos que deben mostrarse en la carta pública (admin: columna «Ocultar» desmarcada → allergens.active = false).
     */
    public function visibleAllergens()
    {
        return $this->belongsToMany(Allergen::class)
            ->where('allergens.active', false);
    }
}
