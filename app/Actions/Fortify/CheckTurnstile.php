<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CheckTurnstile
{
    public function handle(Request $request, $next)
    {
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => config('services.turnstile.secret_key'),
            'response' => $request->input('cf-turnstile-response', ''),
            'remoteip' => $request->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => ['Verifica que no eres un robot.'],
            ]);
        }

        return $next($request);
    }
}
