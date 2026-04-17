<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
        </x-slot>

        <div style="text-align:center;padding:8px 0;">
            <div style="font-size:48px;margin-bottom:16px;">📬</div>
            <h2 style="margin:0 0 10px;font-size:22px;font-weight:800;letter-spacing:-0.02em;">
                Revisa tu correo
            </h2>
            <p style="margin:0;font-size:14px;color:rgba(255,255,255,.65);line-height:1.6;">
                Te hemos enviado un email a<br>
                <strong style="color:#fff;">{{ session('registered_email', 'tu dirección') }}</strong>
            </p>
            <p style="margin:14px 0 0;font-size:14px;color:rgba(255,255,255,.55);line-height:1.6;">
                Haz clic en el botón del email para verificar<br>tu cuenta y crear tu contraseña.
            </p>

            <div style="margin-top:28px;padding:14px 16px;border-radius:12px;background:rgba(255,193,7,.08);border:1px solid rgba(255,193,7,.20);">
                <p style="margin:0;font-size:13px;color:rgba(255,193,7,.90);line-height:1.5;">
                    El enlace caduca en <strong>3 días</strong>.<br>
                    Revisa también la carpeta de spam.
                </p>
            </div>

            {{-- Reenvío de activación --}}
            <div style="margin-top:28px;border-top:1px solid rgba(255,255,255,.10);padding-top:24px;">
                <p style="margin:0 0 14px;font-size:13px;color:rgba(255,255,255,.50);">
                    ¿No te ha llegado? Reenvía el enlace de activación.
                </p>

                @if(session('status'))
                    <p style="margin:0 0 14px;font-size:13px;color:rgba(100,220,130,.90);">
                        {{ session('status') }}
                    </p>
                @endif

                <form method="POST" action="{{ route('register.resend') }}">
                    @csrf
                    <div style="display:flex;gap:8px;align-items:center;justify-content:center;">
                        <input
                            type="email"
                            name="email"
                            value="{{ session('registered_email', old('email')) }}"
                            placeholder="tu@email.com"
                            required
                            style="flex:1;padding:9px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);color:#fff;font-size:13px;outline:none;"
                        >
                        <button
                            type="submit"
                            style="padding:9px 16px;border-radius:8px;background:#fff;color:#111;font-size:13px;font-weight:600;border:none;cursor:pointer;white-space:nowrap;"
                        >
                            Reenviar
                        </button>
                    </div>
                    @error('email')
                        <p style="margin:8px 0 0;font-size:12px;color:rgba(255,100,100,.9);">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            <div style="margin-top:20px;">
                <a href="{{ route('login') }}" style="font-size:13px;color:rgba(255,255,255,.50);text-decoration:underline;">
                    Volver al inicio de sesión
                </a>
            </div>
        </div>
    </x-jet-authentication-card>
</x-guest-layout>
