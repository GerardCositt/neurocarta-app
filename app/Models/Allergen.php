<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Allergen extends Model
{
    use HasFactory, HasTranslations;

    protected array $translatable = ['name'];

    protected $fillable = [
        'name',
        'slug',
        'active',
        'image',
        'is_official',
        'sort_order',
    ];

    protected $casts = [
        'active'      => 'boolean',
        'is_official' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function usesBundledOfficialAsset(): bool
    {
        return $this->image !== null && Str::startsWith($this->image, 'allergens/official/');
    }

    /**
     * URL pública de la imagen (storage subido o pictograma fijo en /public).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        if ($this->usesBundledOfficialAsset()) {
            return asset($this->image);
        }

        return asset('storage/' . $this->image);
    }
}
