<?php

namespace App\Actions\Fortify;

use App\Mail\WelcomeSetPassword;
use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        // Verificar Cloudflare Turnstile (solo si la secret key está configurada)
        $secretKey = config('services.turnstile.secret_key');
        if ($secretKey) {
            try {
                $turnstileResponse = Http::timeout(5)->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => $secretKey,
                    'response' => $input['cf-turnstile-response'] ?? '',
                ]);

                if (! $turnstileResponse->successful() || ! $turnstileResponse->json('success')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cf-turnstile-response' => ['Verifica que no eres un robot.'],
                    ]);
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Si la llamada a Cloudflare falla por red, dejamos pasar (log en producción)
                \Illuminate\Support\Facades\Log::warning('Turnstile check failed: ' . $e->getMessage());
            }
        }

        $plan = $input['plan'] ?? 'trial';
        $validPlans = ['trial', 'basico', 'pro', 'premium'];
        if (! in_array($plan, $validPlans, true)) {
            $plan = 'trial';
        }

        Validator::make($input, [
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'restaurant_name' => ['required', 'string', 'max:255'],
            'phone'           => ['required', 'string', 'max:30'],
        ], [
            'email.unique'             => 'Ya existe una cuenta con ese email.',
            'restaurant_name.required' => 'El nombre del restaurante es obligatorio.',
            'phone.required'           => 'El teléfono es obligatorio.',
        ])->validate();

        // 1. Crear usuario (sin contraseña real ni email verificado aún)
        $user = User::create([
            'name'     => $input['restaurant_name'],
            'email'    => $input['email'],
            'phone'    => $input['phone'],
            'password' => Hash::make(Str::random(32)), // placeholder hasta que creen contraseña
        ]);

        // 2. Crear cuenta y vincular usuario
        $account = Account::create(['name' => $input['restaurant_name']]);
        $account->users()->attach($user->id);

        // 3. Crear restaurante
        $subdomain = $this->generateSubdomain($input['restaurant_name']);
        Restaurant::create([
            'account_id' => $account->id,
            'name'       => $input['restaurant_name'],
            'subdomain'  => $subdomain,
        ]);

        // 4. Crear suscripción
        if ($plan === 'trial') {
            Subscription::create([
                'account_id'            => $account->id,
                'plan_code'             => 'trial',
                'status'                => 'trialing',
                'current_period_end_at' => now()->addDays(7),
            ]);
        } else {
            // Planes de pago: suscripción pendiente hasta que Stripe confirme el pago
            Subscription::create([
                'account_id' => $account->id,
                'plan_code'  => $plan,
                'status'     => 'inactive',
            ]);
        }

        // 5. Guardar en sesión para la redirección y la pantalla de check-email
        session([
            'registered_plan'  => $plan,
            'registered_email' => $user->email,
        ]);

        // 6. Enviar email con enlace firmado para crear contraseña (válido 3 días)
        try {
            $setPasswordUrl = URL::temporarySignedRoute(
                'set-password.show',
                now()->addDays(3),
                ['user' => $user->id]
            );

            Mail::to($user->email)->send(new WelcomeSetPassword($user, $setPasswordUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WelcomeSetPassword mail failed: ' . $e->getMessage());
        }

        return $user;
    }

    private function generateSubdomain(string $restaurantName): string
    {
        $base = Str::slug($restaurantName) ?: 'restaurante';

        $subdomain = $base;
        $i = 2;

        while (Restaurant::where('subdomain', $subdomain)->exists()) {
            $subdomain = $base . '-' . $i;
            $i++;
        }

        return $subdomain;
    }
}
