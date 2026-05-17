<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureE2EUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'e2e:ensure-user {--email=test@test.com} {--password=Password123!}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea o asegura un usuario para tests E2E.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');

        $adminEmail = config('services.filament.admin_email');
        $isDemoAdmin = $adminEmail && $email === $adminEmail;

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name'              => $isDemoAdmin ? 'Admin' : 'E2E Test',
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin'          => $isDemoAdmin,
            ]
        );

        // Si ya existía, refresca password y asegura verificación para demo/admin.
        $user->password = Hash::make($password);
        if ($isDemoAdmin) {
            $user->is_admin = true;
            $user->email_verified_at = $user->email_verified_at ?? now();
        }
        $user->save();

        $this->info("Usuario E2E listo: {$email}");
        return 0;
    }
}
