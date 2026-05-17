<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;

class DemoPublicMenuService
{
    public function ensureUnlocked(Restaurant $restaurant): void
    {
        $restaurant->forceFill([
            'ai_demo_unlimited' => true,
            'ai_credits'        => 999_999,
        ])->save();

        $account = $this->resolveDedicatedAccount($restaurant);

        $hasActive = $account->subscriptions()
            ->orderByDesc('id')
            ->get()
            ->contains(static fn (Subscription $s) => $s->isActive());

        if ($hasActive) {
            return;
        }

        $account->subscriptions()->create([
            'plan_code'             => 'trial',
            'status'                => 'active',
            'current_period_end_at' => now()->addYears(10),
        ]);
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
