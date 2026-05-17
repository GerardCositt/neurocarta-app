<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Services\DemoPublicMenuService;
use Illuminate\Console\Command;

class DemoUnlockPublicCommand extends Command
{
    protected $signature = 'demo:unlock-public
                            {--force : En producción, ejecutar sin confirmación}';

    protected $description = 'Desbloquea la carta pública del restaurante demo (cuenta propia + suscripción activa).';

    public function handle(DemoPublicMenuService $demoPublicMenu): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('¿Desbloquear carta demo en PRODUCCIÓN?', false)) {
                return self::SUCCESS;
            }
        }

        $restaurant = Restaurant::query()->where('subdomain', 'demo')->first();

        if (! $restaurant) {
            $this->error('No existe subdomain «demo». Ejecuta: php artisan demo:ensure --force');

            return self::FAILURE;
        }

        $demoPublicMenu->ensureUnlocked($restaurant);

        $restaurant->refresh()->load('account.subscriptions');

        $this->info("Demo «{$restaurant->name}» (id {$restaurant->id})");
        $this->line('  account_id: '.$restaurant->account_id);
        $this->line('  ai_demo_unlimited: '.($restaurant->ai_demo_unlimited ? 'true' : 'false'));

        $active = $restaurant->account?->subscriptions->first(static fn ($s) => $s->isActive());
        $this->line('  suscripción activa: '.($active ? 'id '.$active->id.' ('.$active->status.')' : 'NINGUNA'));

        return $active ? self::SUCCESS : self::FAILURE;
    }
}
