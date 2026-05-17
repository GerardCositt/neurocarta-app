<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use Illuminate\Console\Command;

class LaunchCheckCommand extends Command
{
    protected $signature = 'launch:check
                            {--skip-tests : No ejecutar PHPUnit}';

    protected $description = 'Comprobaciones automáticas pre-lanzamiento (tests, scheduler, env, demo).';

    public function handle(): int
    {
        $this->info('NeuroCarta — comprobaciones pre-lanzamiento');
        $this->newLine();

        if (! $this->option('skip-tests')) {
            $this->line('▶ Tests críticos…');
            $critical = [
                'tests/Feature/TenantIsolationTest.php',
                'tests/Feature/SubscriptionExpiryTest.php',
                'tests/Feature/PlanFeatureGateTest.php',
                'tests/Feature/AuthenticationTest.php',
                'tests/Feature/RegistrationTest.php',
            ];
            $code = 0;
            foreach ($critical as $file) {
                $code = $this->call('test', [$file]);
                if ($code !== 0) {
                    break;
                }
            }
            if ($code !== 0) {
                $this->error('Tests fallidos. Corrige antes de lanzar.');

                return self::FAILURE;
            }
            $this->info('  Tests OK.');
            $this->newLine();
        }

        $this->line('▶ Scheduler (debe estar en cron: * * * * * php artisan schedule:run)');
        $this->table(
            ['Comando', 'Frecuencia'],
            [
                ['offers:expire', 'diario 00:05'],
                ['trial:send-warnings', 'diario 09:00'],
            ]
        );
        $this->newLine();

        $this->line('▶ Variables de entorno (revisar en producción)');
        $checks = [
            'APP_ENV'               => ['production', 'Entorno producción'],
            'APP_DEBUG'             => ['false', 'Debug desactivado'],
            'APP_URL'               => [null, 'URL pública https://app.neurocarta.ai'],
            'SESSION_DRIVER'        => ['cookie', 'Sesiones en cookie (Render/Docker)'],
            'SESSION_SECURE_COOKIE' => ['true', 'Cookies solo HTTPS'],
            'QUEUE_CONNECTION'      => [null, 'sync OK si pocos usuarios; database/redis si muchos emails'],
            'MAIL_HOST'             => [null, 'SMTP transaccional configurado'],
        ];

        foreach ($checks as $key => [$expected, $hint]) {
            $value = env($key);
            $display = $value === null || $value === '' ? '(vacío)' : (string) $value;
            $ok = $expected === null
                ? ($value !== null && $value !== '')
                : strtolower((string) $value) === strtolower((string) $expected);

            $this->line(sprintf(
                '  %s %s = %s',
                $ok ? '✓' : '!',
                $key,
                $display
            ));
            if (! $ok && $hint) {
                $this->line('      → '.$hint.($expected !== null ? " (esperado: {$expected})" : ''));
            }
        }
        $this->newLine();

        $this->line('▶ Restaurante demo público');
        $demo = Restaurant::where('subdomain', 'demo')->first();
        if (! $demo) {
            $this->warn('  No existe subdomain «demo». Ejecuta: php artisan db:seed --class=RestaurantSeeder && php artisan demo:prepare --subdomain=demo');

            return self::SUCCESS;
        }

        $base = rtrim((string) config('app.url'), '/');
        $public = $base.'/?'.http_build_query(['restaurant' => $demo->id]);
        $subdomainUrl = $this->subdomainPublicUrl($demo);

        $this->info("  «{$demo->name}» (id {$demo->id})");
        if ($subdomainUrl) {
            $this->line('  Carta por subdominio: '.$subdomainUrl);
        }
        $this->line('  Carta con query:        '.$public);
        $this->line('  Preparar menú:          php artisan demo:prepare --subdomain=demo');
        $this->newLine();
        $this->line('  QA manual completo: docs/LAUNCH-QA.md');

        return self::SUCCESS;
    }

    private function subdomainPublicUrl(Restaurant $restaurant): ?string
    {
        if (empty($restaurant->subdomain)) {
            return null;
        }

        $appUrl = (string) config('app.url');
        $host   = parse_url($appUrl, PHP_URL_HOST);
        if (! $host) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
            return null;
        }

        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return null;
        }

        $baseDomain = implode('.', array_slice($parts, -2));
        $scheme     = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

        return $scheme.'://'.$restaurant->subdomain.'.'.$baseDomain.'/';
    }
}
