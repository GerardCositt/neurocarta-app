<?php

namespace App\Console\Commands;

use App\Console\Support\SqlDumpImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ResetDbCommand extends Command
{
    protected $signature = 'reset-db
                            {--force : No pedir confirmación}
                            {--skip-demo : Sin DemoMenuSeeder ni e2e tras migrar sin dump}';

    protected $description = 'Vacía la base de datos e importa CARTA_IMPORT_SQL si existe; si no, migrate:fresh + seeds.';

    public function handle(): int
    {
        if (app()->isProduction() || config('app.env') === 'production') {
            $this->error('reset-db está deshabilitado en producción. Este comando destruye la base de datos y nunca debe ejecutarse en un entorno real.');

            return 1;
        }

        if (! $this->option('force') && ! $this->confirm('¿Borrar todos los datos de la base de datos actual?', false)) {
            return 1;
        }

        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if ($connection['driver'] === 'sqlite') {
            $path = $connection['database'];
            if (File::exists($path)) {
                File::delete($path);
            }
            $dir = dirname($path);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put($path, '');
        } else {
            try {
                Artisan::call('db:wipe', ['--force' => true]);
                $this->output->write(Artisan::output());
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return 1;
            }
        }

        if (SqlDumpImporter::fileExists()) {
            $this->info('Importando volcado: '.SqlDumpImporter::importPath());
            try {
                SqlDumpImporter::importDump();
                $this->info('Importación SQL completada.');
                Artisan::call('storage:link');

                return 0;
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return 1;
            }
        }

        $this->line('Sin fichero SQL; ejecutando migrate:fresh + seeds…');

        Artisan::call('migrate:fresh', ['--force' => true]);
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
        $this->info('Base de datos reiniciada.');

        return 0;
    }
}
