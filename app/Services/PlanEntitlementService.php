<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Scopes\RestaurantScope;
use App\Models\Subscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PlanEntitlementService
{
    public const PLAN_BASIC = 'basico';
    public const PLAN_PRO = 'pro';
    public const PLAN_PREMIUM = 'premium';

    /**
     * Límites (cuotas) a nivel CUENTA (sumando todos los restaurantes).
     * Premium => null (sin límite).
     */
    private const LIMITS = [
        self::PLAN_BASIC => [
            'restaurants' => 1,
            'products' => 40,
            'categories' => 4,
        ],
        self::PLAN_PRO => [
            'restaurants' => 2,
            'products' => 100,
            'categories' => 10,
        ],
        self::PLAN_PREMIUM => [
            'restaurants' => 5,
            'products' => null,
            'categories' => null,
        ],
    ];

    public function effectivePlanForAccount(?Account $account): string
    {
        // Demo: siempre Pro
        if (app()->environment('demo')) {
            return self::PLAN_PRO;
        }

        if (! $account) {
            return self::PLAN_BASIC;
        }

        $sub = $this->activeSubscription($account);
        if ($sub) {
            $plan = (string) $sub->plan_code;

            // Trial activo → acceso total (equivalente a Premium)
            if ($plan === 'trial') {
                return self::PLAN_PREMIUM;
            }

            if (in_array($plan, [self::PLAN_BASIC, self::PLAN_PRO, self::PLAN_PREMIUM], true)) {
                return $plan;
            }
        }

        return self::PLAN_BASIC;
    }

    public function limitsForPlan(string $plan): array
    {
        $plan = in_array($plan, [self::PLAN_BASIC, self::PLAN_PRO, self::PLAN_PREMIUM], true)
            ? $plan
            : self::PLAN_BASIC;

        return Arr::get(self::LIMITS, $plan, self::LIMITS[self::PLAN_BASIC]);
    }

    public function activeSubscription(Account $account): ?Subscription
    {
        return $account->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->orderByDesc('id')
            ->get()
            ->first(static fn (Subscription $s) => $s->isActive());
    }

    public function assertCanCreateRestaurant(Account $account): void
    {
        $plan = $this->effectivePlanForAccount($account);
        $limit = $this->limitsForPlan($plan)['restaurants'];
        if ($limit === null) {
            return;
        }

        $current = (int) $account->restaurants()->count();
        if ($current >= (int) $limit) {
            throw new \RuntimeException($this->limitMessage('restaurants', $plan, $limit));
        }
    }

    public function assertCanCreateCategory(Account $account): void
    {
        $plan = $this->effectivePlanForAccount($account);
        $limit = $this->limitsForPlan($plan)['categories'];
        if ($limit === null) {
            return;
        }

        $current = (int) $this->countCategoriesForAccount($account);
        if ($current >= (int) $limit) {
            throw new \RuntimeException($this->limitMessage('categories', $plan, $limit));
        }
    }

    public function assertCanCreateProduct(Account $account): void
    {
        $plan = $this->effectivePlanForAccount($account);
        $limit = $this->limitsForPlan($plan)['products'];
        if ($limit === null) {
            return;
        }

        $current = (int) $this->countProductsForAccount($account);
        if ($current >= (int) $limit) {
            throw new \RuntimeException($this->limitMessage('products', $plan, $limit));
        }
    }

    public function accountForRestaurant(?Restaurant $restaurant): ?Account
    {
        if (! $restaurant) {
            return null;
        }

        return $restaurant->account_id ? Account::find($restaurant->account_id) : null;
    }

    private function countProductsForAccount(Account $account): int
    {
        return (int) Product::withoutGlobalScope(RestaurantScope::class)
            ->whereIn('restaurant_id', $account->restaurants()->select('id'))
            ->count();
    }

    private function countCategoriesForAccount(Account $account): int
    {
        return (int) Category::withoutGlobalScope(RestaurantScope::class)
            ->whereIn('restaurant_id', $account->restaurants()->select('id'))
            ->count();
    }

    private function limitMessage(string $kind, string $plan, $limit): string
    {
        $planName = $plan === self::PLAN_BASIC ? 'Básico' : ($plan === self::PLAN_PRO ? 'Pro' : 'Premium');
        $label = $kind === 'restaurants' ? 'restaurantes'
            : ($kind === 'products' ? 'productos' : 'categorías');

        return "Has alcanzado el límite de {$label} del plan {$planName} ({$limit}). Mejora tu plan para continuar.";
    }
}

