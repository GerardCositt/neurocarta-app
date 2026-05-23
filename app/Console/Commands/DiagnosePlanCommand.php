<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Models\Scopes\RestaurantScope;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Console\Command;

class DiagnosePlanCommand extends Command
{
    protected $signature = 'app:diagnose-plan
                            {--email= : User email to inspect}
                            {--restaurant-id= : Restaurant id to inspect}';

    protected $description = 'Diagnose effective plan, subscriptions and product/category usage for a user/account/restaurant';

    public function handle(PlanEntitlementService $svc): int
    {
        $email = trim((string) $this->option('email'));
        $restaurantId = $this->option('restaurant-id');

        $restaurant = null;
        $user = null;

        if ($restaurantId !== null && $restaurantId !== '') {
            $restaurant = Restaurant::withoutGlobalScope(RestaurantScope::class)->find((int) $restaurantId);
            if (! $restaurant) {
                $this->error('Restaurant not found for --restaurant-id=' . $restaurantId);
                return self::FAILURE;
            }
        }

        if ($email !== '') {
            $user = User::whereRaw('LOWER(email)=?', [mb_strtolower($email)])->first();
            if (! $user) {
                $this->error('User not found for --email=' . $email);
                return self::FAILURE;
            }
        }

        if (! $restaurant && $user) {
            $restaurant = Restaurant::withoutGlobalScope(RestaurantScope::class)
                ->whereIn('account_id', $user->accounts()->select('accounts.id'))
                ->orderBy('id')
                ->first();
        }

        if (! $restaurant) {
            $this->error('No restaurant resolved. Pass --restaurant-id or a user with at least one account/restaurant.');
            return self::FAILURE;
        }

        $account = $svc->accountForRestaurant($restaurant);
        if (! $account) {
            $this->error('Restaurant has no account associated (account_id is null/invalid).');
            return self::FAILURE;
        }

        $plan = $svc->effectivePlanForAccount($account);
        $limits = $svc->limitsForPlan($plan);

        $restaurantIds = $account->restaurants()->pluck('id');
        $products = \App\Models\Product::withoutGlobalScope(RestaurantScope::class)
            ->whereIn('restaurant_id', $restaurantIds)
            ->count();
        $categories = \App\Models\Category::withoutGlobalScope(RestaurantScope::class)
            ->whereIn('restaurant_id', $restaurantIds)
            ->count();
        $restaurants = $account->restaurants()->count();

        $this->line('App environment: ' . app()->environment());
        $this->line('DB connection: ' . config('database.default'));
        $this->line('DB database: ' . (string) config('database.connections.' . config('database.default') . '.database'));
        $this->newLine();

        $this->line('Restaurant: #' . $restaurant->id . ' ' . $restaurant->name . ' (' . $restaurant->subdomain . ')');
        $this->line('Account: #' . $account->id . ' ' . $account->name);
        if ($user) {
            $this->line('User: #' . $user->id . ' ' . $user->email);
        }
        $this->line('Effective plan: ' . $plan);

        $this->newLine();
        $this->info('Subscriptions for account (latest first):');
        $subs = $account->subscriptions()->orderByDesc('id')->get();
        if ($subs->isEmpty()) {
            $this->line('- none');
        } else {
            foreach ($subs as $sub) {
                /** @var Subscription $sub */
                $this->line(sprintf(
                    '- id=%d plan=%s status=%s isActive=%s period_end=%s',
                    $sub->id,
                    (string) $sub->plan_code,
                    (string) $sub->status,
                    $sub->isActive() ? 'yes' : 'no',
                    (string) ($sub->current_period_end_at ?? 'null')
                ));
            }
        }

        $this->newLine();
        $this->info('Usage vs limits (account-level):');
        $this->line(sprintf('- restaurants: %d / %s', $restaurants, $limits['restaurants'] ?? 'unlimited'));
        $this->line(sprintf('- categories: %d / %s', $categories, $limits['categories'] ?? 'unlimited'));
        $this->line(sprintf('- products: %d / %s', $products, $limits['products'] ?? 'unlimited'));

        $this->newLine();
        $this->info('Feature flags:');
        foreach (['ai', 'csv_import', 'translations', 'offers'] as $feature) {
            $this->line(sprintf('- %s: %s', $feature, $svc->planHasFeature($plan, $feature) ? 'enabled' : 'disabled'));
        }

        return self::SUCCESS;
    }
}
