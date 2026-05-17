<?php

namespace App\Support;

use App\Models\Restaurant;
use App\Services\PlanEntitlementService;

class PlanFeatureGate
{
    public static function allows(string $feature): bool
    {
        if (auth()->user()?->is_admin) {
            return true;
        }

        $svc     = app(PlanEntitlementService::class);
        $account = app()->bound('account') ? app('account') : null;

        if (! $account) {
            $rid     = session('admin_restaurant_id');
            $account = $rid ? Restaurant::find($rid)?->account : null;
        }

        return $svc->planHasFeature($svc->effectivePlanForAccount($account), $feature);
    }
}
