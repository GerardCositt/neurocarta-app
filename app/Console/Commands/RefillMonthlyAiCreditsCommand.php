<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\PlanEntitlementService;
use Illuminate\Console\Command;

class RefillMonthlyAiCreditsCommand extends Command
{
    protected $signature = 'credits:monthly-refill {--dry-run : Show what would be updated without saving}';
    protected $description = 'Set AI credits to the monthly allowance for each active plan (runs on the 1st of each month)';

    private const MONTHLY_CREDITS = [
        'pro'     => 300,
        'premium' => 300,
    ];

    public function handle(PlanEntitlementService $plans): int
    {
        $dry = $this->option('dry-run');
        $updated = 0;

        Account::with(['subscriptions', 'restaurants'])->get()->each(function (Account $account) use ($plans, $dry, &$updated) {
            $plan = $plans->effectivePlanForAccount($account);
            $allowance = self::MONTHLY_CREDITS[$plan] ?? null;

            if ($allowance === null) {
                return;
            }

            $account->restaurants->each(function ($restaurant) use ($allowance, $dry, &$updated) {
                if ($dry) {
                    $this->line("DRY-RUN: restaurant #{$restaurant->id} ({$restaurant->name}) → {$allowance} credits (was {$restaurant->ai_credits})");
                } else {
                    $restaurant->update(['ai_credits' => $allowance]);
                }
                $updated++;
            });
        });

        $this->info($dry ? "DRY-RUN: {$updated} restaurants would receive monthly credits." : "Done: {$updated} restaurants refilled.");

        return self::SUCCESS;
    }
}
