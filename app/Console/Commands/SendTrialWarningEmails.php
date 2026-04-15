<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingReminder;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialWarningEmails extends Command
{
    protected $signature = 'trial:send-warnings';
    protected $description = 'Envía emails de aviso a usuarios cuyo trial termina en 2 días (día 5) o hoy (día 7)';

    public function handle(): int
    {
        $now = now();

        // Día 5 del trial: quedan ~2 días (entre 1 y 3 días hasta expirar)
        $day5Subs = Subscription::query()
            ->where('status', 'trialing')
            ->whereNull('trial_warning_day5_sent_at')
            ->whereBetween('current_period_end_at', [
                $now->copy()->addDays(1),
                $now->copy()->addDays(3),
            ])
            ->with('account.users')
            ->get();

        foreach ($day5Subs as $sub) {
            $this->sendToAccountUsers($sub, 2);
            $sub->update(['trial_warning_day5_sent_at' => now()]);
            $this->info("Day-5 warning sent for subscription #{$sub->id}");
        }

        // Día 7 del trial: expira hoy (entre 0 y 1 día hasta expirar)
        $day7Subs = Subscription::query()
            ->where('status', 'trialing')
            ->whereNull('trial_warning_day7_sent_at')
            ->whereBetween('current_period_end_at', [
                $now->copy(),
                $now->copy()->addDay(),
            ])
            ->with('account.users')
            ->get();

        foreach ($day7Subs as $sub) {
            $this->sendToAccountUsers($sub, 0);
            $sub->update(['trial_warning_day7_sent_at' => now()]);
            $this->info("Day-7 warning sent for subscription #{$sub->id}");
        }

        $total = $day5Subs->count() + $day7Subs->count();
        $this->info("Done. {$total} email(s) sent.");

        return self::SUCCESS;
    }

    private function sendToAccountUsers(Subscription $sub, int $daysLeft): void
    {
        $users = $sub->account?->users ?? collect();

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new TrialEndingReminder($user, $daysLeft));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error(
                    "TrialEndingReminder failed for user #{$user->id}: " . $e->getMessage()
                );
            }
        }
    }
}
