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

            <div style="margin-top:24px;">
                <a href="{{ route('login') }}" style="font-size:13px;color:rgba(255,255,255,.50);text-decoration:underline;">
                    Volver al inicio de sesión
                </a>
            </div>
        </div>
    </x-jet-authentication-card>
</x-guest-layout>
