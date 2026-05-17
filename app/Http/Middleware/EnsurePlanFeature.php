<?php

namespace App\Http\Middleware;

use App\Services\PlanEntitlementService;
use Closure;
use Illuminate\Http\Request;

class EnsurePlanFeature
{
    public function __construct(private PlanEntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next, string $feature)
    {
        if ($request->user()?->is_admin) {
            return $next($request);
        }

        $account = app()->bound('account') ? app('account') : null;
        $plan    = $this->entitlements->effectivePlanForAccount($account);

        if (! $this->entitlements->planHasFeature($plan, $feature)) {
            return redirect()->route('dashboard')
                ->with('plan_error', __('admin.plan.feature_not_available'));
        }

        return $next($request);
    }
}
