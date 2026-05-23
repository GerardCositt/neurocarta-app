<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckEmailCommand extends Command
{
    protected $signature = 'app:check-email {email : Email to check}';

    protected $description = 'Show current runtime environment/DB connection and whether an email exists in users table';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $connection = config('database.default');
        $databaseName = (string) config("database.connections.{$connection}.database");
        $host = (string) config("database.connections.{$connection}.host");
        $port = (string) config("database.connections.{$connection}.port");
        $appEnv = app()->environment();
        $appUrl = (string) config('app.url');

        $this->line('App environment: ' . $appEnv);
        $this->line('App URL: ' . $appUrl);
        $this->line('DB connection: ' . $connection);
        $this->line('DB database: ' . $databaseName);
        if ($host !== '') {
            $this->line('DB host: ' . $host);
        }
        if ($port !== '') {
            $this->line('DB port: ' . $port);
        }

        $exactCount = DB::table('users')->where('email', $email)->count();
        $caseInsensitiveCount = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->count();

        $this->newLine();
        $this->line('Email checked: ' . $email);
        $this->line('Exact match count: ' . $exactCount);
        $this->line('Case-insensitive count: ' . $caseInsensitiveCount);

        $matches = DB::table('users')
            ->select('id', 'email', 'created_at')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->orderBy('id')
            ->get();

        if ($matches->isEmpty()) {
            $this->info('Result: no users found with that email in this runtime DB.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Matching users:');
        foreach ($matches as $row) {
            $this->line(sprintf('- id=%s email=%s created_at=%s', $row->id, $row->email, $row->created_at));
        }

        return self::SUCCESS;
    }
}
