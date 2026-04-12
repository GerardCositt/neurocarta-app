<x-guest-layout>
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:56px 20px;">

    {{-- Logo --}}
    <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;margin-bottom:40px;">
        <span style="font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;font-size:32px;font-weight:900;letter-spacing:-0.02em;line-height:1;">
            <span style="color:#ffffff;">NeuroCarta</span><span style="color:#FFC107;font-weight:900;">.ai</span><span style="vertical-align:super;font-size:10px;color:rgba(255,255,255,.70);">®</span>
        </span>
    </a>

    {{-- Encabezado --}}
    <div style="text-align:center;margin-bottom:40px;max-width:560px;">
        <h1 style="margin:0 0 10px;font-size:36px;font-weight:900;letter-spacing:-0.02em;line-height:1.1;">
            Elige tu plan
        </h1>
        <p style="margin:0;font-size:16px;color:rgba(255,255,255,.65);line-height:1.6;">
            Empieza gratis 7 días con acceso total. Sin tarjeta.
        </p>
    </div>

    {{-- Grid de planes --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;width:100%;max-width:940px;">

        {{-- Premium --}}
        <a href="{{ route('register.plan', 'premium') }}" style="text-decoration:none;">
            <div class="plan-card" style="border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.03);">
                <div style="margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;">Premium</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">249€<span style="font-size:16px;font-weight:400;color:rgba(255,255,255,.50);">/mes</span></div>
                <div style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;">Facturación anual</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ 2.000 productos</li>
                    <li>✓ 200 categorías</li>
                    <li>✓ IA ilimitada</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:rgba(255,255,255,.08);color:#fff;">Elegir Premium</div>
            </div>
        </a>

        {{-- Pro --}}
        <a href="{{ route('register.plan', 'pro') }}" style="text-decoration:none;">
            <div class="plan-card" style="border:1px solid rgba(197,36,57,.40);background:rgba(197,36,57,.06);position:relative;">
                <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#c52439;color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;white-space:nowrap;letter-spacing:.04em;">
                    MÁS POPULAR
                </div>
                <div style="margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;">Pro</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">129€<span style="font-size:16px;font-weight:400;color:rgba(255,255,255,.50);">/mes</span></div>
                <div style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;">Facturación anual</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ 500 productos</li>
                    <li>✓ 60 categorías</li>
                    <li>✓ IA + traducciones + CSV</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:#c52439;color:#fff;">Elegir Pro</div>
            </div>
        </a>

        {{-- Básico --}}
        <a href="{{ route('register.plan', 'basico') }}" style="text-decoration:none;">
            <div class="plan-card" style="border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.03);">
                <div style="margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;">Básico</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">59€<span style="font-size:16px;font-weight:400;color:rgba(255,255,255,.50);">/mes</span></div>
                <div style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;">Facturación anual</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ 100 productos</li>
                    <li>✓ 20 categorías</li>
                    <li>✗ Sin IA ni traducciones</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:rgba(255,255,255,.08);color:#fff;">Elegir Básico</div>
            </div>
        </a>

        {{-- Trial --}}
        <a href="{{ route('register.plan', 'trial') }}" style="text-decoration:none;">
            <div class="plan-card" style="border:1px solid rgba(255,193,7,.35);background:rgba(255,193,7,.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:#FFC107;text-transform:uppercase;letter-spacing:.06em;">Trial</span>
                    <span style="font-size:11px;background:rgba(255,193,7,.18);color:#FFC107;padding:3px 8px;border-radius:20px;font-weight:700;">7 días gratis</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">0€</div>
                <div style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;">Sin tarjeta</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ Acceso total sin límites</li>
                    <li>✓ IA + traducciones incluidas</li>
                    <li>✓ Soporte incluido</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:#FFC107;color:#0F0F0F;">Empezar gratis</div>
            </div>
        </a>

    </div>

    {{-- Footer --}}
    <div style="margin-top:40px;text-align:center;font-size:13px;color:rgba(255,255,255,.40);">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" style="color:rgba(255,255,255,.70);text-decoration:underline;">Inicia sesión</a>
    </div>

</div>

<style>
.plan-card {
    border-radius: 16px;
    padding: 24px;
    transition: transform .15s ease, box-shadow .15s ease;
    cursor: pointer;
    height: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}
.plan-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 60px -20px rgba(0,0,0,.6);
}
.plan-btn {
    border-radius: 10px;
    padding: 11px 16px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    margin-top: auto;
}
@media (max-width: 600px) {
    h1 { font-size: 28px !important; }
}
</style>
</x-guest-layout>
