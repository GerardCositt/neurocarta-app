<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use Illuminate\Console\Command;

class PrepareDemoCommand extends Command
{
    protected $signature = 'demo:prepare
                            {--restaurant= : ID del restaurante (por defecto el primero por id)}
                            {--unlimited-ai : Activa IA ilimitada (demo) en ese restaurante}
                            {--force : En production, permite --unlimited-ai sin confirmación interactiva}';

    protected $description = 'Prepara datos y muestra enlaces para enseñar la carta y el panel (presentaciones, ferias, reuniones).';

    public function handle(): int
    {
        $rid = $this->option('restaurant');
        $restaurant = $rid
            ? Restaurant::query()->find($rid)
            : Restaurant::query()->orderBy('id')->first();

        if (! $restaurant) {
            $this->error('No hay restaurantes en la base de datos. Ejecuta: php artisan db:seed --class=RestaurantSeeder');

            return 1;
        }

        $this->call('db:seed', ['--class' => \Database\Seeders\DemoMenuSeeder::class]);

        if ($this->option('unlimited-ai')) {
            if (app()->environment('production') && ! $this->option('force')) {
                $this->error('En production usa: php artisan demo:prepare --unlimited-ai --force (o quita --unlimited-ai).');

                return 1;
            }
            $restaurant->forceFill(['ai_demo_unlimited' => true])->save();
            $this->info('IA ilimitada (demo) activada para «'.$restaurant->name.'».');
        }

        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            $base = 'http://127.0.0.1:8000';
        }

        $publicUrl = $base.'/?'.http_build_query(['restaurant' => $restaurant->id]);

        $this->newLine();
        $this->line('────────────────────────────────────────────────────────────');
        $this->info('Demo lista: «'.$restaurant->name.'» (id '.$restaurant->id.', subdomain '.$restaurant->subdomain.')');
        $this->line('────────────────────────────────────────────────────────────');
        $this->line('Carta pública (comparte esta URL en el móvil del cliente o proyector):');
        $this->line('  '.$publicUrl);
        $this->newLine();
        $this->line('Panel admin (necesitas usuario registrado e inicio de sesión):');
        $this->line('  '.$base.'/product');
        $this->line('  '.$base.'/login');
        $this->newLine();
        $this->line('Acceso sin subdominio: el parámetro ?restaurant= es necesario en 127.0.0.1 / IP / túnel.');
        $this->line('Guía ampliada: docs/DEMO.md');
        $this->newLine();

        return 0;
    }
}
