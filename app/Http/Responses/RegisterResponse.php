<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        // All web registrations: log out (Fortify auto-logins after register) and
        // send to the check-email screen. The user verifies email, sets a password,
        // then logs in. If their subscription is inactive/expired they reach
        // subscription.expired and can checkout from there.
        Auth::logout();

        return redirect()->route('register.check-email');
    }
}
