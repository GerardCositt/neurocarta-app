<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyTurnstile
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('cf-turnstile-response');

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => config('services.turnstile.secret_key'),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            return back()
                ->withErrors(['cf-turnstile-response' => 'Verifica que no eres un robot.'])
                ->withInput();
        }

        return $next($request);
    }
}
