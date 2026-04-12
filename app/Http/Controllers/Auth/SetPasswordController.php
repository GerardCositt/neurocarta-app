<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;

class SetPasswordController extends Controller
{
    /**
     * Muestra el formulario para crear contraseña.
     * La URL es una URL firmada temporalmente (válida 3 días).
     */
    public function show(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403, 'El enlace ha caducado o no es válido.');

        // Generar un token de reset de contraseña de Laravel para usarlo en el POST
        $token = Password::createToken($user);

        $formAction = route('set-password.store');

        return view('auth.set-password', [
            'token'      => $token,
            'email'      => $user->email,
            'formAction' => $formAction,
        ]);
    }

    /**
     * Procesa la creación de contraseña, verifica el email y hace login.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'           => Hash::make($password),
                    'email_verified_at'  => now(),
                ])->save();

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
