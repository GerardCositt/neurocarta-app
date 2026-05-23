<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'subdomain',
        'currency',
        'ai_credits',
        'ai_demo_unlimited',
    ];

    private const CURRENCY_SYMBOLS = [
        'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'MXN' => '$',
        'COP' => '$', 'ARS' => '$', 'BRL' => 'R$', 'CLP' => '$',
        'PEN' => 'S/', 'JPY' => '¥', 'CNY' => '¥', 'CHF' => 'Fr',
    ];

    public function currencySymbol(): string
    {
        return self::CURRENCY_SYMBOLS[$this->currency ?? 'EUR'] ?? ($this->currency ?? '€');
    }

    public static function priceContainsSymbol(string $price): bool
    {
        foreach (array_unique(array_values(self::CURRENCY_SYMBOLS)) as $symbol) {
            if (str_contains($price, $symbol)) {
                return true;
            }
        }
        return false;
    }

    public function formatPrice(string $price): string
    {
        if (self::priceContainsSymbol($price)) {
            return $price;
        }
        return trim($price) . ' ' . $this->currencySymbol();
    }

    protected $casts = [
        'ai_demo_unlimited' => 'boolean',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function pairings()
    {
        return $this->hasMany(Pairing::class);
    }

    public function advices()
    {
        return $this->hasMany(Advice::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function aiUsageLogs()
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(RestaurantApiKey::class);
    }

    /** Carta de ventas / ferias: demo.neurocarta.ai o flag explícito en BD. */
    public function isPublicSalesDemo(): bool
    {
        if ($this->ai_demo_unlimited) {
            return true;
        }

        $demoSub = (string) config('neurocarta.demo_subdomain', 'demo');

        return $demoSub !== '' && $this->subdomain === $demoSub;
    }
}
