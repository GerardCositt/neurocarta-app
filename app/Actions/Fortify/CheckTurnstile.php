<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CheckTurnstile
{
    public function handle(Request $request, $next)
    {
        $secretKey = config('services.turnstile.secret_key');

        if (! $secretKey) {
            return $next($request);
        }

        try {
            $response = Http::timeout(5)->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $request->input('cf-turnstile-response', ''),
                'remoteip' => $request->ip(),
            ]);

            if (! $response->successful() || ! $response->json('success')) {
                throw ValidationException::withMessages([
                    'cf-turnstile-response' => ['Verifica que no eres un robot.'],
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Turnstile login check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
