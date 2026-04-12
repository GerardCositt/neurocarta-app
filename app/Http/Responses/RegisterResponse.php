<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $plan = session('registered_plan', 'trial');

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        // Planes de pago → stub Stripe (próximamente)
        if (in_array($plan, ['basico', 'pro', 'premium'], true)) {
            return redirect()->route('checkout.pending');
        }

        // Trial gratuito → pantalla "revisa tu correo"
        return redirect()->route('register.check-email');
    }
}
