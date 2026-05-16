<?php

namespace App\Console\Commands;

use App\Console\Support\SqlDumpImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SetupCommand extends Command
{
    protected $signature = 'setup
                            {--skip-import : No importar SQL aunque exista el fichero y la BD esté vacía}
                            {--skip-demo : Tras migrar sin dump, no ejecutar DemoMenuSeeder ni e2e:ensure-user}
                            {--force : En producción, no pedir confirmación}';

    protected $description = 'Prepara entorno local: .env, APP_KEY, directorios; si la BD está vacía importa CARTA_IMPORT_SQL o ejecuta migraciones + seeds.';

    public function handle(): int
    {
        $base = base_path();

        if (! File::isFile($base.'/.env') && File::isFile($base.'/.env.example')) {
            File::copy($base.'/.env.example', $base.'/.env');
            $this->info('Creado .env desde .env.example');
        }

        foreach ([
            $base.'/database',
            $base.'/database/backups',
            $base.'/storage/framework/cache/data',
            $base.'/storage/framework/sessions',
            $base.'/storage/framework/testing',
            $base.'/storage/framework/views',
            $base.'/storage/logs',
            $base.'/bootstrap/cache',
        ] as $dir) {
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        if (config('app.env') === 'production' && ! $this->option('force')
            && ! $this->confirm('¿Ejecutar setup en producción?', false)) {
            return 1;
        }

        $envFile = $base.'/.env';
        if (File::isFile($envFile)) {
            $envBody = File::get($envFile);
            if (! preg_match('/^APP_KEY=base64:[A-Za-z0-9+\/=]+/m', $envBody)) {
                Artisan::call('key:generate', ['--force' => true]);
                $this->info('APP_KEY generada.');
            }
        }

        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            if (! File::exists($path)) {
                File::put($path, '');
            }
        }

        if (! SqlDumpImporter::isDatabaseEmpty()) {
            $this->warn('La base de datos ya tiene tablas; no se importa ni se ejecutan migraciones.');

            return 0;
        }

        if (! $this->option('skip-import') && SqlDumpImporter::fileExists()) {
            $this->info('Importando volcado: '.SqlDumpImporter::importPath());
            try {
                SqlDumpImporter::importDump();
                $this->info('Importación SQL completada.');
                Artisan::call('storage:link');
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                $this->line('Si el dump es de MySQL/MariaDB, configura DB_CONNECTION=mysql y DB_* como en README.md / .devcontainer.');

                return 1;
            }

            return 0;
        }

        if (SqlDumpImporter::fileExists() && $this->option('skip-import')) {
            $this->warn('Saltando importación (--skip-import).');
        } elseif (! SqlDumpImporter::fileExists()) {
            $this->line('No hay fichero SQL en '.SqlDumpImporter::importPath().' (opcional). Ejecutando migraciones y seeds…');
        }

        Artisan::call('migrate', ['--force' => true]);
        $this->output->write(Artisan::output());

        Artisan::call('db:seed', ['--force' => true]);
        $this->output->write(Artisan::output());

        if (! $this->option('skip-demo')) {
            Artisan::call('db:seed', ['--class' => 'DemoMenuSeeder', '--force' => true]);
            $this->output->write(Artisan::output());
            Artisan::call('e2e:ensure-user', [
                '--email' => 'test@test.com',
                '--password' => 'Password123!',
            ]);
            $this->output->write(Artisan::output());
        }

        Artisan::call('storage:link');
        $this->info('Setup terminado.');

        return 0;
    }
}
