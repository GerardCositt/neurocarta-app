<x-guest-layout>
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:56px 20px;">

    {{-- Logo --}}
    <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;margin-bottom:40px;">
        <span style="font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;font-size:32px;font-weight:900;letter-spacing:-0.02em;line-height:1;">
            <span style="color:#ffffff;">NeuroCarta</span><span style="color:#FFC107;font-weight:900;">.ai</span><span style="vertical-align:super;font-size:10px;color:rgba(255,255,255,.70);">®</span>
        </span>
    </a>

    {{-- Encabezado --}}
    <div style="text-align:center;margin-bottom:32px;max-width:560px;">
        <div style="font-size:48px;margin-bottom:16px;">⏰</div>
        <h1 style="margin:0 0 12px;font-size:32px;font-weight:900;letter-spacing:-0.02em;line-height:1.1;">
            Tu prueba gratuita ha terminado
        </h1>
        <p style="margin:0;font-size:16px;color:rgba(255,255,255,.60);line-height:1.6;">
            Han pasado los 7 días de prueba. Elige un plan para seguir usando NeuroCarta.ai®<br>
            y mantener tu carta, productos y configuración.
        </p>
    </div>

    {{-- Errores del checkout --}}
    @if($errors->has('checkout'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(197,36,57,.12);border:1px solid rgba(197,36,57,.35);max-width:500px;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,120,120,.90);">{{ $errors->first('checkout') }}</p>
        </div>
    @endif

    {{-- Mensajes de estado (cancel, etc.) --}}
    @if(session('status'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);max-width:500px;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,255,255,.65);">{{ session('status') }}</p>
        </div>
    @endif

    {{-- Selector mensual / anual --}}
    <div style="display:inline-flex;align-items:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);border-radius:30px;padding:4px;margin-bottom:28px;gap:2px;">
        <button id="btn-monthly" type="button" onclick="selectInterval('monthly')"
                style="border:none;border-radius:26px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;background:rgba(255,255,255,.15);color:#fff;transition:all .15s ease;">
            Mensual
        </button>
        <button id="btn-annual" type="button" onclick="selectInterval('annual')"
                style="border:none;border-radius:26px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;background:transparent;color:rgba(255,255,255,.55);transition:all .15s ease;">
            Anual
            <span style="display:inline-block;margin-left:6px;font-size:11px;background:rgba(255,193,7,.18);color:#FFC107;padding:2px 7px;border-radius:20px;font-weight:700;">2 meses gratis</span>
        </button>
    </div>

    {{-- Grid de planes --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;width:100%;max-width:820px;">

        {{-- Pro (destacado) --}}
        <form method="POST" action="{{ route('checkout.create') }}" style="display:contents;">
            @csrf
            <input type="hidden" name="plan" value="pro">
            <input type="hidden" name="interval" value="monthly" class="interval-input">
            <div class="plan-card" style="border:1px solid rgba(197,36,57,.40);background:rgba(197,36,57,.06);position:relative;" onclick="this.closest('form').submit()">
                <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#c52439;color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;white-space:nowrap;letter-spacing:.04em;">
                    MÁS POPULAR
                </div>
                <div style="margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;">Pro</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">
                    <span class="plan-price" data-monthly="35€" data-annual="350€">35€</span><span class="plan-unit" style="font-size:16px;font-weight:400;color:rgba(255,255,255,.50);" data-monthly="/mes" data-annual="/año">/mes</span>
                </div>
                <div class="plan-subtitle" style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;" data-monthly="350€/año · 2 meses gratis" data-annual="2 meses gratis">350€/año · 2 meses gratis</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ 500 productos</li>
                    <li>✓ 60 categorías</li>
                    <li>✓ IA + traducciones + CSV</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:#c52439;color:#fff;">Elegir Pro</div>
            </div>
        </form>

        {{-- Básico --}}
        <form method="POST" action="{{ route('checkout.create') }}" style="display:contents;">
            @csrf
            <input type="hidden" name="plan" value="basico">
            <input type="hidden" name="interval" value="monthly" class="interval-input">
            <div class="plan-card" style="border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.03);" onclick="this.closest('form').submit()">
                <div style="margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;">Básico</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">
                    <span class="plan-price" data-monthly="25€" data-annual="250€">25€</span><span class="plan-unit" style="font-size:16px;font-weight:400;color:rgba(255,255,255,.50);" data-monthly="/mes" data-annual="/año">/mes</span>
                </div>
                <div class="plan-subtitle" style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;" data-monthly="250€/año · 2 meses gratis" data-annual="2 meses gratis">250€/año · 2 meses gratis</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ 100 productos</li>
                    <li>✓ 20 categorías</li>
                    <li>✗ Sin IA ni traducciones</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:rgba(255,255,255,.08);color:#fff;">Elegir Básico</div>
            </div>
        </form>

        {{-- Premium --}}
        <form method="POST" action="{{ route('checkout.create') }}" style="display:contents;">
            @csrf
            <input type="hidden" name="plan" value="premium">
            <input type="hidden" name="interval" value="monthly" class="interval-input">
            <div class="plan-card" style="border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.03);" onclick="this.closest('form').submit()">
                <div style="margin-bottom:14px;">
                    <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;">Premium</span>
                </div>
                <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">
                    <span class="plan-price" data-monthly="65€" data-annual="650€">65€</span><span class="plan-unit" style="font-size:16px;font-weight:400;color:rgba(255,255,255,.50);" data-monthly="/mes" data-annual="/año">/mes</span>
                </div>
                <div class="plan-subtitle" style="font-size:13px;color:rgba(255,255,255,.50);margin-bottom:20px;" data-monthly="650€/año · 2 meses gratis" data-annual="2 meses gratis">650€/año · 2 meses gratis</div>
                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);">
                    <li>✓ 2.000 productos</li>
                    <li>✓ 200 categorías</li>
                    <li>✓ IA ilimitada</li>
                </ul>
                <div class="plan-btn" style="margin-top:24px;background:rgba(255,255,255,.08);color:#fff;">Elegir Premium</div>
            </div>
        </form>

    </div>

    {{-- Logout --}}
    <div style="margin-top:32px;text-align:center;">
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:13px;color:rgba(255,255,255,.35);text-decoration:underline;padding:0;">
                Cerrar sesión
            </button>
        </form>
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
    pointer-events: none; /* click handled by parent card onclick */
}
@media (max-width: 600px) {
    h1 { font-size: 26px !important; }
}
</style>

<script>
function selectInterval(val) {
    // Update all hidden interval inputs
    document.querySelectorAll('.interval-input').forEach(function(el) {
        el.value = val;
    });

    // Update price amounts (data-monthly / data-annual)
    document.querySelectorAll('.plan-price').forEach(function(el) {
        el.textContent = val === 'monthly' ? el.dataset.monthly : el.dataset.annual;
    });

    // Update price units (/mes or /año)
    document.querySelectorAll('.plan-unit').forEach(function(el) {
        el.textContent = val === 'monthly' ? el.dataset.monthly : el.dataset.annual;
    });

    // Update subtitles
    document.querySelectorAll('.plan-subtitle').forEach(function(el) {
        el.textContent = val === 'monthly' ? el.dataset.monthly : el.dataset.annual;
    });

    // Update toggle button styles
    var isMonthly = val === 'monthly';
    document.getElementById('btn-monthly').style.background = isMonthly ? 'rgba(255,255,255,.15)' : 'transparent';
    document.getElementById('btn-monthly').style.color      = isMonthly ? '#fff' : 'rgba(255,255,255,.55)';
    document.getElementById('btn-annual').style.background  = isMonthly ? 'transparent' : 'rgba(255,255,255,.15)';
    document.getElementById('btn-annual').style.color       = isMonthly ? 'rgba(255,255,255,.55)' : '#fff';
}
</script>
</x-guest-layout>
