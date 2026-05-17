<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Restaurant;

class DemoPublicMenuService
{
    public function ensureUnlocked(Restaurant $restaurant): void
    {
        $restaurant->forceFill([
            'ai_demo_unlimited' => true,
            'ai_credits'        => 999_999,
        ])->save();

        $account = $this->resolveDedicatedAccount($restaurant);

        $subscription = $account->subscriptions()->orderByDesc('id')->first();

        $premium = [
            'plan_code'             => PlanEntitlementService::PLAN_PREMIUM,
            'status'                => 'active',
            'current_period_end_at' => now()->addYears(10),
        ];

        if ($subscription) {
            $subscription->update($premium);
        } else {
            $account->subscriptions()->create($premium);
        }
    }

    private function resolveDedicatedAccount(Restaurant $restaurant): Account
    {
        $account = $restaurant->account;

        if ($account && $account->restaurants()->where('id', '!=', $restaurant->id)->doesntExist()) {
            return $account;
        }

        $dedicated = Account::create(['name' => 'NeuroCarta Demo']);
        $restaurant->update(['account_id' => $dedicated->id]);

        return $dedicated;
    }
}
