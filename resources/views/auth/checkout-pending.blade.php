<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
        </x-slot>

        <div style="text-align:center;padding:8px 0;">
            <div style="font-size:48px;margin-bottom:16px;">🔒</div>
            <h2 style="margin:0 0 10px;font-size:22px;font-weight:800;letter-spacing:-0.02em;">
                Pago próximamente
            </h2>
            <p style="margin:0;font-size:14px;color:rgba(255,255,255,.65);line-height:1.6;">
                La pasarela de pago está en construcción.<br>
                Tu cuenta ha sido creada. Te avisaremos cuando esté lista.
            </p>
            <div style="margin-top:24px;">
                <a href="{{ route('login') }}" style="font-size:13px;color:rgba(255,255,255,.50);text-decoration:underline;">
                    Ir al login
                </a>
            </div>
        </div>
    </x-jet-authentication-card>
</x-guest-layout>
