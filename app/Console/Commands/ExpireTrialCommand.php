<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class ExpireTrialCommand extends Command
{
    protected $signature = 'trial:expire
                            {email : Email del usuario}
                            {--force : Permitir en producción}';

    protected $description = 'Marca el trial activo de la cuenta como caducado (útil para QA manual).';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('En producción usa --force si estás seguro.');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Usuario no encontrado.');

            return self::FAILURE;
        }

        $account = $user->accounts()->first();
        if (! $account) {
            $this->error('El usuario no tiene cuenta asociada.');

            return self::FAILURE;
        }

        $sub = $account->subscriptions()
            ->where('status', 'trialing')
            ->orderByDesc('id')
            ->first();

        if (! $sub) {
            $this->error('No hay suscripción en trial para esta cuenta.');

            return self::FAILURE;
        }

        $sub->update(['current_period_end_at' => now()->subDay()]);
        $this->info("Trial caducado para {$user->email} (subscription #{$sub->id}).");

        return self::SUCCESS;
    }
}
