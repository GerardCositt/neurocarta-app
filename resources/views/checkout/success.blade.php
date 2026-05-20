<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
        </x-slot>

        <div style="text-align:center;padding:8px 0;">
            <img src="{{ asset('img/butterfly.svg') }}" alt="" width="64" height="64" style="display:block;margin:0 auto 16px;filter:drop-shadow(0 0 16px rgba(255,122,0,0.5));">
            <h2 style="margin:0 0 10px;font-size:22px;font-weight:800;letter-spacing:-0.02em;">
                Suscripción en proceso
            </h2>
            <p style="margin:0 0 16px;font-size:14px;color:rgba(255,255,255,.65);line-height:1.6;">
                Stripe está confirmando tu suscripción. La activación puede tardar unos segundos.
                Recibirás un email de confirmación cuando esté lista.
            </p>
            <p style="margin:0 0 24px;font-size:13px;color:rgba(255,255,255,.40);line-height:1.5;">
                Si el panel aún no muestra el plan activo, espera unos segundos y recarga la página.
            </p>
            <a href="{{ route('dashboard') }}"
               style="display:inline-block;padding:12px 28px;background:#c52439;color:#fff;text-decoration:none;border-radius:10px;font-size:14px;font-weight:700;">
                Ir al panel
            </a>
        </div>
    </x-jet-authentication-card>
</x-guest-layout>
