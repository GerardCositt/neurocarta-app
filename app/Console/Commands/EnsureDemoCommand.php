<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Restaurant;
use Illuminate\Console\Command;

class EnsureDemoCommand extends Command
{
    protected $signature = 'demo:ensure
                            {--menu : Insertar menú de ejemplo si no hay categorías}
                            {--force : En producción, ejecutar sin confirmación}';

    protected $description = 'Crea o actualiza el restaurante NeuroCarta Demo (subdomain=demo) si no existe.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('¿Crear/actualizar demo en PRODUCCIÓN?', false)) {
                return self::SUCCESS;
            }
        }

        $restaurant = Restaurant::updateOrCreate(
            ['subdomain' => 'demo'],
            [
                'name'              => 'NeuroCarta Demo',
                'ai_demo_unlimited' => true,
                'ai_credits'        => 999_999,
            ]
        );

        $this->info("Restaurante demo: «{$restaurant->name}» (id {$restaurant->id}, subdomain demo)");

        if ($this->option('menu') || ! Category::where('restaurant_id', $restaurant->id)->exists()) {
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\DemoMenuSeeder', '--force' => true]);
        } else {
            $this->line('Menú demo ya existía; usa --menu para forzar recarga (vacía categorías antes).');
        }

        $base = rtrim((string) config('app.url'), '/') ?: 'http://127.0.0.1:8000';
        $this->newLine();
        $this->line('Carta pública: '.$base.'/?restaurant='.$restaurant->id);
        $host = parse_url($base, PHP_URL_HOST);
        if ($host && ! filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost') {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
                $baseDomain = implode('.', array_slice($parts, -2));
                $this->line('Subdominio (si DNS existe): '.$scheme.'://demo.'.$baseDomain.'/');
            }
        }

        return self::SUCCESS;
    }
}
