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
            $this->line('▶ Tests críticos (suite Launch, 5 archivos / ~12 tests)…');
            $phpunit = base_path('vendor/bin/phpunit');
            if (! is_file($phpunit)) {
                $this->error('Ejecuta composer install primero.');

                return self::FAILURE;
            }

            $process = proc_open(
                [PHP_BINARY, $phpunit, '--testsuite=Launch', '--testdox', '--colors=always'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                base_path()
            );

            if (! is_resource($process)) {
                $this->error('No se pudo ejecutar PHPUnit.');

                return self::FAILURE;
            }

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);

            $this->output->write($stdout ?: '');
            if ($stderr) {
                $this->output->write($stderr);
            }

            if ($code !== 0) {
                $this->newLine();
                $this->error('Tests fallidos (exit '.$code.'). Alternativa: ./scripts/launch-test.sh');

                return self::FAILURE;
            }
            $this->newLine();
            $this->info('  Tests Launch OK.');
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

        $this->line('▶ Variables de entorno (valores efectivos en runtime)');
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
            $value = $this->resolveLaunchConfigValue($key);
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

        // --- HTTPS ---
        $this->line('▶ HTTPS');
        $appUrl = (string) config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            $this->line('  ✓ APP_URL usa HTTPS');
        } else {
            $this->error('  ✗ APP_URL no usa HTTPS → '.$appUrl);
        }
        $this->newLine();

        // --- Stripe ---
        $this->line('▶ Stripe');
        $stripeKey    = config('cashier.key') ?: env('STRIPE_KEY', '');
        $stripeSecret = config('cashier.secret') ?: env('STRIPE_SECRET', '');
        $stripeWebhook = env('STRIPE_WEBHOOK_SECRET', '');

        foreach ([
            ['STRIPE_KEY',            $stripeKey,     'pk_live_'],
            ['STRIPE_SECRET',         $stripeSecret,  'sk_live_'],
            ['STRIPE_WEBHOOK_SECRET', $stripeWebhook, 'whsec_'],
        ] as [$label, $val, $livePrefix]) {
            if (! $val) {
                $this->error("  ✗ {$label} vacío — configúralo en .env");
            } elseif (! str_starts_with((string) $val, $livePrefix)) {
                $this->warn("  ⚠ {$label} parece clave TEST (no live) — prefijo esperado: {$livePrefix}");
            } else {
                $this->line("  ✓ {$label} = ".substr((string) $val, 0, 12).'…');
            }
        }

        foreach ([
            'STRIPE_PRICE_BASICO_MONTHLY',
            'STRIPE_PRICE_BASICO_ANNUAL',
            'STRIPE_PRICE_PRO_MONTHLY',
            'STRIPE_PRICE_PRO_ANNUAL',
            'STRIPE_PRICE_PREMIUM_MONTHLY',
            'STRIPE_PRICE_PREMIUM_ANNUAL',
        ] as $label) {
            $val = env($label, '');
            if (! $val) {
                $this->error("  ✗ {$label} vacío — crea el Price en Stripe y pon el price_...");
            } elseif (! str_starts_with((string) $val, 'price_')) {
                $this->warn("  ⚠ {$label} no parece un Price ID de Stripe — valor: {$val}");
            } else {
                $this->line("  ✓ {$label} = ".substr((string) $val, 0, 16).'…');
            }
        }
        $this->line('  → Impuestos: Prices sin IVA incluido. En Stripe Tax usa tax_behavior=exclusive.');
        $this->newLine();

        // --- Base de datos ---
        $this->line('▶ Base de datos');
        try {
            \DB::connection()->getPdo();
            $count = \DB::table('users')->count();
            $this->line("  ✓ Conexión OK — {$count} usuarios en BD");
        } catch (\Throwable $e) {
            $this->error('  ✗ No se puede conectar: '.$e->getMessage());
        }
        $this->newLine();

        // --- Storage ---
        $this->line('▶ Storage');
        $testFile = storage_path('app/launch_check_test.tmp');
        try {
            file_put_contents($testFile, 'ok');
            unlink($testFile);
            $this->line('  ✓ Storage escribible');
        } catch (\Throwable $e) {
            $this->error('  ✗ Storage no escribible: '.$e->getMessage());
        }
        $diskPublic = public_path('storage');
        if (is_link($diskPublic) || is_dir($diskPublic)) {
            $this->line('  ✓ public/storage enlazado');
        } else {
            $this->warn('  ⚠ public/storage no existe — ejecuta: php artisan storage:link');
        }
        $this->newLine();

        // --- Email (test de conexión SMTP, sin enviar) ---
        $this->line('▶ Email SMTP');
        $mailHost = config('mail.mailers.smtp.host');
        $mailPort = config('mail.mailers.smtp.port');
        $mailFrom = config('mail.from.address');
        if (! $mailHost) {
            $this->error('  ✗ MAIL_HOST vacío');
        } else {
            $this->line("  ✓ Host: {$mailHost}:{$mailPort}");
            $this->line("  ✓ From: {$mailFrom}");
            $this->line('  → Para probar envío real: php artisan tinker → Mail::raw("test", fn($m) => $m->to("tu@email.com")->subject("Test"))');
        }
        $this->newLine();

        // --- Admin ---
        $this->line('▶ Admin');
        $adminEmail = config('services.filament.admin_email');
        if ($adminEmail) {
            $this->line("  ✓ FILAMENT_ADMIN_EMAIL = {$adminEmail}");
        } else {
            $this->warn('  ⚠ FILAMENT_ADMIN_EMAIL vacío — solo accede admin si is_admin=true en BD');
        }
        $this->newLine();

        $this->line('▶ Restaurante demo público');
        $demo = Restaurant::where('subdomain', 'demo')->first();
        if (! $demo) {
            $this->warn('  No existe subdomain «demo». Ejecuta: php artisan demo:ensure --menu --force');
            $this->warn('  En Docker: bash scripts/ensure-demo-docker.sh');

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

    /**
     * Valores efectivos en runtime (config cacheada). env() devuelve null tras config:cache.
     */
    private function resolveLaunchConfigValue(string $key): ?string
    {
        switch ($key) {
            case 'APP_ENV':
                return config('app.env');
            case 'APP_DEBUG':
                return config('app.debug') ? 'true' : 'false';
            case 'APP_URL':
                return config('app.url');
            case 'SESSION_DRIVER':
                return config('session.driver');
            case 'SESSION_SECURE_COOKIE':
                $secure = config('session.secure');

                return $secure === null ? null : ($secure ? 'true' : 'false');
            case 'QUEUE_CONNECTION':
                return config('queue.default');
            case 'MAIL_HOST':
                return config('mail.mailers.smtp.host');
            default:
                return env($key);
        }
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
