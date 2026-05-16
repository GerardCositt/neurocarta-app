<?php

namespace App\Console\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Importación de volcados .sql según README.md / RUTINA_CODESPACE.md (database/backups/).
 * Ruta del fichero: env CARTA_IMPORT_SQL o database/backups/carta-bar-jaen-v2.sql por defecto.
 */
class SqlDumpImporter
{
    public static function importPath(): string
    {
        return base_path(env('CARTA_IMPORT_SQL', 'database/backups/carta-bar-jaen-v2.sql'));
    }

    public static function fileExists(): bool
    {
        $p = self::importPath();

        return is_file($p) && filesize($p) > 0;
    }

    /**
     * Base “vacía”: sin tablas de aplicación (tras clonar sin dump o fichero sqlite recién creado).
     */
    public static function isDatabaseEmpty(): bool
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if ($connection['driver'] === 'sqlite') {
            $path = $connection['database'];
            if (! is_file($path) || filesize($path) === 0) {
                return true;
            }
        }

        try {
            if ($connection['driver'] === 'sqlite') {
                $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

                return count($rows) === 0;
            }

            if ($connection['driver'] === 'mysql') {
                $db = $connection['database'];
                $rows = DB::select('SHOW TABLES');

                return count($rows) === 0;
            }
        } catch (\Throwable $e) {
            return true;
        }

        return false;
    }

    /**
     * @throws \RuntimeException
     */
    public static function importDump(): void
    {
        if (! self::fileExists()) {
            throw new \RuntimeException('No existe el fichero SQL en '.self::importPath());
        }

        $sql = File::get(self::importPath());
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if ($connection['driver'] === 'sqlite') {
            $dbPath = $connection['database'];
            $dir = dirname($dbPath);
            if (! is_dir($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $process = new Process(['sqlite3', $dbPath], null, null, $sql);
            $process->setTimeout(3600);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new \RuntimeException(
                    'Importación SQLite fallida: '.$process->getErrorOutput().$process->getOutput()
                );
            }

            return;
        }

        if ($connection['driver'] === 'mysql') {
            $host = $connection['host'];
            $port = (string) ($connection['port'] ?? '3306');
            $user = $connection['username'];
            $pass = (string) ($connection['password'] ?? '');
            $database = $connection['database'];

            $mysqlBin = self::findMysqlClient();
            if ($mysqlBin === null) {
                throw new \RuntimeException(
                    'No se encontró el cliente «mysql» en PATH. En local: instala MariaDB/MySQL client o usa el flujo del README (mysql < database/backups/…).'
                );
            }

            $cmd = [$mysqlBin, '-h', $host, '-P', $port, '-u', $user];
            if ($pass !== '') {
                $cmd[] = '-p'.$pass;
            }
            $cmd[] = $database;

            $process = new Process($cmd, null, null, $sql);
            $process->setTimeout(3600);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new \RuntimeException(
                    'Importación MySQL fallida: '.$process->getErrorOutput().$process->getOutput()
                );
            }

            return;
        }

        throw new \RuntimeException('Driver no soportado para importación automática: '.$connection['driver']);
    }

    private static function findMysqlClient(): ?string
    {
        $candidates = ['mysql', '/usr/bin/mysql', '/usr/local/bin/mysql'];
        foreach ($candidates as $bin) {
            if ($bin === 'mysql') {
                $which = @shell_exec('command -v mysql 2>/dev/null');
                if ($which) {
                    return trim($which);
                }

                continue;
            }
            if (is_executable($bin)) {
                return $bin;
            }
        }

        return null;
    }
}
