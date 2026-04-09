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
        'ai_credits',
        'ai_demo_unlimited',
    ];

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
}
