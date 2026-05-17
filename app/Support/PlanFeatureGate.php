<?php

namespace App\Support;

use App\Models\Restaurant;
use App\Services\PlanEntitlementService;
use Illuminate\Support\Facades\RateLimiter;

class PlanFeatureGate
{
    public static function allows(string $feature): bool
    {
        if (auth()->user()?->is_admin) {
            return true;
        }

        $restaurant = app()->bound('restaurant') ? app('restaurant') : null;
        if (! $restaurant) {
            $rid = session('admin_restaurant_id');
            $restaurant = $rid ? Restaurant::find($rid) : null;
        }
        if ($restaurant?->isPublicSalesDemo()) {
            return true;
        }

        $svc     = app(PlanEntitlementService::class);
        $account = app()->bound('account') ? app('account') : null;

        if (! $account) {
            $account = $restaurant?->account;
        }

        return $svc->planHasFeature($svc->effectivePlanForAccount($account), $feature);
    }

    /** Throttle OpenAI/Livewire AI actions: 30 per minute per user. */
    public static function attemptAiAction(): bool
    {
        if (auth()->user()?->is_admin) {
            return true;
        }

        $key = 'ai-actions:'.(auth()->id() ?? request()->ip());

        return RateLimiter::attempt($key, 30, static fn () => null, 60);
    }
}
