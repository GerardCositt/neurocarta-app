<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Console\Command;

class DevSetPlanCommand extends Command
{
    protected $signature = 'dev:set-plan
                            {plan : basico | pro | premium | trial}
                            {--email= : email del usuario (por defecto el primero que tenga suscripción)}
                            {--force : permite ejecutarlo en producción}';

    protected $description = 'Cambia el plan de un usuario para pruebas manuales.';

    private const VALID_PLANS = [
        PlanEntitlementService::PLAN_BASIC,
        PlanEntitlementService::PLAN_PRO,
        PlanEntitlementService::PLAN_PREMIUM,
        'trial',
    ];

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Este comando es solo para entornos de desarrollo.');
            $this->line('Usa --force si realmente quieres ejecutarlo en producción.');
            return 1;
        }

        $plan = $this->argument('plan');
        if (! in_array($plan, self::VALID_PLANS, true)) {
            $this->error('Plan inválido. Usa: ' . implode(', ', self::VALID_PLANS));
            return 1;
        }

        $email = $this->option('email');
        $user  = $email
            ? User::where('email', $email)->firstOrFail()
            : User::whereHas('accounts.subscriptions')->first();

        if (! $user) {
            $this->error('No se encontró ningún usuario con suscripción.');
            return 1;
        }

        $account = $user->accounts()->first();
        if (! $account) {
            $this->error("El usuario {$user->email} no tiene cuenta.");
            return 1;
        }

        $sub = $account->subscriptions()->latest()->first();
        if (! $sub) {
            $sub = new Subscription(['account_id' => $account->id]);
        }

        [$planCode, $status] = $plan === 'trial'
            ? ['trial', 'trialing']
            : [$plan, 'active'];

        $sub->plan_code             = $planCode;
        $sub->status                = $status;
        $sub->current_period_end_at = $plan === 'trial' ? null : now()->addMonth();
        $sub->save();

        $label = match($plan) {
            'trial'   => 'Trial (trialing)',
            'basico'  => 'Básico (active)',
            'pro'     => 'Pro (active)',
            'premium' => 'Premium (active)',
        };

        $this->info("✓ {$user->email} → {$label}");

        if (app()->isProduction()) {
            $this->warn('Ejecutado en PRODUCCIÓN con --force. Recuerda restaurar el plan real cuando termines.');
        }

        return 0;
    }
}
