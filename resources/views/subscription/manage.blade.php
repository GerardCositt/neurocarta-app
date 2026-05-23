<x-guest-layout>
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:56px 20px;">

    {{-- Logo --}}
    <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;margin-bottom:32px;">
        <span style="font-family:'Inter',ui-sans-serif,system-ui,sans-serif;font-size:32px;font-weight:900;letter-spacing:0;line-height:1;">
            <span style="color:#ffffff;">NeuroCarta</span><span style="color:#FFC107;font-weight:900;">.ai</span><span style="vertical-align:super;font-size:10px;color:rgba(255,255,255,.70);">®</span>
        </span>
    </a>

    @php
        $user    = auth()->user();
        $account = $user ? $user->accounts()->first() : null;
        $sub     = $account ? $account->subscriptions()->latest()->first() : null;
        $currentPlan     = $sub?->plan_code ?? '';
        $currentInterval = $sub?->billing_interval ?? 'monthly';
        $hasStripe       = $sub && $sub->stripe_customer_id;
        $periodEnd       = $sub?->current_period_end_at;

        $planLabels = ['basico' => 'Básico', 'pro' => 'Pro', 'premium' => 'Premium', 'trial' => 'Trial'];
        $intervalLabel = match($currentInterval) { 'annual' => 'anual', default => 'mensual' };
    @endphp

    @if(session('status'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);max-width:860px;width:100%;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,255,255,.65);">{{ session('status') }}</p>
        </div>
    @endif
    @if($errors->has('portal'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(197,36,57,.12);border:1px solid rgba(197,36,57,.35);max-width:860px;width:100%;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,120,120,.90);">{{ $errors->first('portal') }}</p>
        </div>
    @endif

    {{-- Cabecera --}}
    <div style="width:100%;max-width:860px;margin-bottom:24px;">
        <h1 style="margin:0 0 4px;font-size:26px;font-weight:900;letter-spacing:-0.02em;">Cambia tu plan</h1>
        <p style="margin:0;font-size:15px;color:rgba(255,255,255,.50);">
            Plan actual: <strong style="color:rgba(255,255,255,.80);">{{ $planLabels[$currentPlan] ?? 'Sin plan' }}</strong>
            @if($currentInterval) <span style="color:rgba(255,255,255,.35);">· {{ $intervalLabel }}</span> @endif
            @if($periodEnd) <span style="color:rgba(255,255,255,.30);">· Renovación {{ $periodEnd->format('d M Y') }}</span> @endif
        </p>
    </div>

    {{-- Toggle mensual / anual --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:28px;">
        <button id="btn-monthly" onclick="setInterval('monthly')"
                style="padding:8px 20px;border-radius:20px;border:none;cursor:pointer;font-size:13px;font-weight:700;background:#fff;color:#0f0f0f;transition:all .15s;">
            Mensual
        </button>
        <button id="btn-annual" onclick="setInterval('annual')"
                style="padding:8px 20px;border-radius:20px;border:1px solid rgba(255,255,255,.20);cursor:pointer;font-size:13px;font-weight:700;background:transparent;color:rgba(255,255,255,.60);transition:all .15s;">
            Anual <span style="font-size:11px;background:rgba(255,193,7,.18);color:#FFC107;padding:2px 6px;border-radius:10px;margin-left:4px;">1 mes gratis</span>
        </button>
    </div>

    {{-- Tarjetas de plan --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;width:100%;max-width:860px;">

        {{-- Básico --}}
        <div style="border:1px solid {{ $currentPlan === 'basico' ? 'rgba(255,255,255,.40)' : 'rgba(255,255,255,.10)' }};background:{{ $currentPlan === 'basico' ? 'rgba(255,255,255,.07)' : 'rgba(255,255,255,.03)' }};border-radius:16px;padding:28px 24px;display:flex;flex-direction:column;gap:0;">
            @if($currentPlan === 'basico')
            <div style="display:inline-block;margin-bottom:12px;font-size:11px;font-weight:700;background:rgba(255,255,255,.12);color:rgba(255,255,255,.70);padding:3px 10px;border-radius:20px;width:fit-content;">PLAN ACTUAL</div>
            @endif
            <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Básico</div>
            <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">
                <span class="price-monthly">25€</span><span class="price-annual" style="display:none;">275€</span>
                <span class="unit-monthly" style="font-size:16px;font-weight:400;color:rgba(255,255,255,.40);">/mes</span>
                <span class="unit-annual" style="display:none;font-size:16px;font-weight:400;color:rgba(255,255,255,.40);">/año</span>
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,.35);margin-bottom:20px;">
                <span class="sub-monthly">275€/año con facturación anual</span>
                <span class="sub-annual" style="display:none;">1 mes gratis incluido</span>
            </div>
            <ul style="list-style:none;margin:0 0 24px;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.70);flex:1;">
                <li>✓ 70 productos · 6 categorías</li>
                <li>✓ 1 restaurante</li>
                <li style="color:rgba(255,255,255,.35);">— Sin IA ni traducciones</li>
                <li style="color:rgba(255,255,255,.35);">— Sin créditos IA incluidos</li>
            </ul>
            @if($currentPlan === 'basico')
                <div style="width:100%;padding:12px;border-radius:10px;text-align:center;font-size:13px;font-weight:700;background:rgba(255,255,255,.06);color:rgba(255,255,255,.35);box-sizing:border-box;">Plan actual</div>
            @elseif($hasStripe)
                <form method="POST" action="{{ route('subscription.portal') }}">
                    @csrf
                    <button type="submit" style="width:100%;padding:12px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700;background:rgba(255,255,255,.10);color:#fff;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.18)'" onmouseout="this.style.background='rgba(255,255,255,.10)'">
                        Cambiar a Básico →
                    </button>
                </form>
            @else
                <a href="{{ route('checkout.start', ['plan' => 'basico']) }}" style="display:block;width:100%;box-sizing:border-box;padding:12px;border-radius:10px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;background:rgba(255,255,255,.10);color:#fff;">Activar Básico →</a>
            @endif
        </div>

        {{-- Pro --}}
        <div style="border:1px solid {{ $currentPlan === 'pro' ? 'rgba(197,36,57,.60)' : 'rgba(197,36,57,.30)' }};background:{{ $currentPlan === 'pro' ? 'rgba(197,36,57,.10)' : 'rgba(197,36,57,.05)' }};border-radius:16px;padding:28px 24px;display:flex;flex-direction:column;gap:0;position:relative;">
            @if($currentPlan !== 'pro')
            <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#c52439;color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;white-space:nowrap;letter-spacing:.04em;">MÁS POPULAR</div>
            @endif
            @if($currentPlan === 'pro')
            <div style="display:inline-block;margin-bottom:12px;font-size:11px;font-weight:700;background:rgba(197,36,57,.25);color:rgba(255,120,130,.90);padding:3px 10px;border-radius:20px;width:fit-content;">PLAN ACTUAL</div>
            @endif
            <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Pro</div>
            <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">
                <span class="price-monthly">35€</span><span class="price-annual" style="display:none;">385€</span>
                <span class="unit-monthly" style="font-size:16px;font-weight:400;color:rgba(255,255,255,.40);">/mes</span>
                <span class="unit-annual" style="display:none;font-size:16px;font-weight:400;color:rgba(255,255,255,.40);">/año</span>
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,.35);margin-bottom:20px;">
                <span class="sub-monthly">385€/año con facturación anual</span>
                <span class="sub-annual" style="display:none;">1 mes gratis incluido</span>
            </div>
            <ul style="list-style:none;margin:0 0 24px;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);flex:1;">
                <li>✓ 250 productos · 15 categorías</li>
                <li>✓ 2 restaurantes</li>
                <li>✓ IA + traducciones + CSV</li>
                <li>✓ <strong>200 créditos IA/mes</strong></li>
            </ul>
            @if($currentPlan === 'pro')
                <div style="width:100%;padding:12px;border-radius:10px;text-align:center;font-size:13px;font-weight:700;background:rgba(197,36,57,.15);color:rgba(255,120,130,.60);box-sizing:border-box;">Plan actual</div>
            @elseif($hasStripe)
                <form method="POST" action="{{ route('subscription.portal') }}">
                    @csrf
                    <button type="submit" style="width:100%;padding:12px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700;background:#c52439;color:#fff;transition:background .15s;" onmouseover="this.style.background='#a81e2e'" onmouseout="this.style.background='#c52439'">
                        Cambiar a Pro →
                    </button>
                </form>
            @else
                <a href="{{ route('checkout.start', ['plan' => 'pro']) }}" style="display:block;width:100%;box-sizing:border-box;padding:12px;border-radius:10px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;background:#c52439;color:#fff;">Activar Pro →</a>
            @endif
        </div>

        {{-- Premium --}}
        <div style="border:1px solid {{ $currentPlan === 'premium' ? 'rgba(255,193,7,.50)' : 'rgba(255,255,255,.10)' }};background:{{ $currentPlan === 'premium' ? 'rgba(255,193,7,.06)' : 'rgba(255,255,255,.03)' }};border-radius:16px;padding:28px 24px;display:flex;flex-direction:column;gap:0;">
            @if($currentPlan === 'premium')
            <div style="display:inline-block;margin-bottom:12px;font-size:11px;font-weight:700;background:rgba(255,193,7,.18);color:#FFC107;padding:3px 10px;border-radius:20px;width:fit-content;">PLAN ACTUAL</div>
            @endif
            <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Premium</div>
            <div style="font-size:34px;font-weight:900;letter-spacing:-0.02em;margin-bottom:4px;">
                <span class="price-monthly">69€</span><span class="price-annual" style="display:none;">759€</span>
                <span class="unit-monthly" style="font-size:16px;font-weight:400;color:rgba(255,255,255,.40);">/mes</span>
                <span class="unit-annual" style="display:none;font-size:16px;font-weight:400;color:rgba(255,255,255,.40);">/año</span>
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,.35);margin-bottom:20px;">
                <span class="sub-monthly">759€/año con facturación anual</span>
                <span class="sub-annual" style="display:none;">1 mes gratis incluido</span>
            </div>
            <ul style="list-style:none;margin:0 0 24px;padding:0;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(255,255,255,.80);flex:1;">
                <li>✓ 1.000 productos · 100 categorías</li>
                <li>✓ 3 restaurantes</li>
                <li>✓ IA + traducciones + CSV</li>
                <li>✓ <strong>400 créditos IA/mes</strong></li>
            </ul>
            @if($currentPlan === 'premium')
                <div style="width:100%;padding:12px;border-radius:10px;text-align:center;font-size:13px;font-weight:700;background:rgba(255,193,7,.08);color:rgba(255,193,7,.50);box-sizing:border-box;">Plan actual</div>
            @elseif($hasStripe)
                <form method="POST" action="{{ route('subscription.portal') }}">
                    @csrf
                    <button type="submit" style="width:100%;padding:12px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700;background:rgba(255,193,7,.15);color:#FFC107;transition:background .15s;" onmouseover="this.style.background='rgba(255,193,7,.25)'" onmouseout="this.style.background='rgba(255,193,7,.15)'">
                        Cambiar a Premium →
                    </button>
                </form>
            @else
                <a href="{{ route('checkout.start', ['plan' => 'premium']) }}" style="display:block;width:100%;box-sizing:border-box;padding:12px;border-radius:10px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;background:rgba(255,193,7,.15);color:#FFC107;">Activar Premium →</a>
            @endif
        </div>

    </div>

    {{-- Nota prorratas --}}
    @if($hasStripe)
    <div style="margin-top:20px;max-width:860px;width:100%;text-align:center;">
        <p style="font-size:12px;color:rgba(255,255,255,.30);margin:0;">
            Las mejoras se aplican al momento (Stripe cobra solo la parte proporcional). Las bajadas y cancelaciones se aplican al finalizar el periodo pagado.
        </p>
    </div>
    @endif

    {{-- Volver --}}
    <div style="margin-top:28px;text-align:center;">
        <a href="{{ route('product') }}" style="font-size:13px;color:rgba(255,255,255,.40);text-decoration:underline;">← Volver al panel</a>
        <span style="color:rgba(255,255,255,.15);margin:0 10px;">·</span>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:13px;color:rgba(255,255,255,.35);text-decoration:underline;padding:0;">Cerrar sesión</button>
        </form>
    </div>

</div>

<script>
function setInterval(mode) {
    var isAnnual = mode === 'annual';
    document.querySelectorAll('.price-monthly').forEach(function(el){ el.style.display = isAnnual ? 'none' : ''; });
    document.querySelectorAll('.price-annual').forEach(function(el){ el.style.display = isAnnual ? '' : 'none'; });
    document.querySelectorAll('.unit-monthly').forEach(function(el){ el.style.display = isAnnual ? 'none' : ''; });
    document.querySelectorAll('.unit-annual').forEach(function(el){ el.style.display = isAnnual ? '' : 'none'; });
    document.querySelectorAll('.sub-monthly').forEach(function(el){ el.style.display = isAnnual ? 'none' : ''; });
    document.querySelectorAll('.sub-annual').forEach(function(el){ el.style.display = isAnnual ? '' : 'none'; });
    document.getElementById('btn-monthly').style.background = isAnnual ? 'transparent' : '#fff';
    document.getElementById('btn-monthly').style.color = isAnnual ? 'rgba(255,255,255,.60)' : '#0f0f0f';
    document.getElementById('btn-monthly').style.border = isAnnual ? '1px solid rgba(255,255,255,.20)' : 'none';
    document.getElementById('btn-annual').style.background = isAnnual ? '#fff' : 'transparent';
    document.getElementById('btn-annual').style.color = isAnnual ? '#0f0f0f' : 'rgba(255,255,255,.60)';
    document.getElementById('btn-annual').style.border = isAnnual ? 'none' : '1px solid rgba(255,255,255,.20)';
}
// Init con intervalo actual
setInterval('{{ $currentInterval ?? "monthly" }}');
</script>
</x-guest-layout>
