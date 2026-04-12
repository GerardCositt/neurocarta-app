<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
        </x-slot>

        <h2 style="margin:0 0 6px;font-size:20px;font-weight:800;letter-spacing:-0.02em;">
            Crea tu contraseña
        </h2>
        <p style="margin:0 0 22px;font-size:13px;color:rgba(255,255,255,.55);">
            Ya casi estás. Elige una contraseña para acceder a tu cuenta.
        </p>

        @if ($errors->any())
            <div style="margin-bottom:16px;padding:12px 14px;border-radius:10px;background:rgba(197,36,57,.15);border:1px solid rgba(197,36,57,.35);">
                @foreach ($errors->all() as $error)
                    <p style="margin:0;font-size:13px;color:#ff8a94;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <x-jet-label for="password" value="Contraseña" />
                <x-jet-input id="password" class="block mt-1 w-full" type="password"
                    name="password" required autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres" />
            </div>

            <div class="mt-4">
                <x-jet-label for="password_confirmation" value="Repetir contraseña" />
                <x-jet-input id="password_confirmation" class="block mt-1 w-full" type="password"
                    name="password_confirmation" required autocomplete="new-password"
                    placeholder="Igual que arriba" />
            </div>

            <div style="margin-top:20px;">
                <button type="submit" style="width:100%;padding:13px;border-radius:12px;border:none;cursor:pointer;font-size:15px;font-weight:800;background:#FFC107;color:#0F0F0F;">
                    Activar cuenta
                </button>
            </div>
        </form>
    </x-jet-authentication-card>
</x-guest-layout>
