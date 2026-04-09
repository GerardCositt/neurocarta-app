<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pairing extends Model
{
    use HasFactory, HasTranslations;

    protected array $translatable = ['name', 'description'];

    /**

     * The attributes that are mass assignable.

     *

     * @var array

     */

    protected $fillable = [
        'name',
        'description',
        'restaurant_id',
        'active',
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
        return $this->hasMany(Product::class);
    }

}
