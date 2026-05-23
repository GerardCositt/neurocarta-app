<x-guest-layout>
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:56px 20px;">

    <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;margin-bottom:32px;">
        <span style="font-family:'Inter',ui-sans-serif,system-ui,sans-serif;font-size:32px;font-weight:900;letter-spacing:0;line-height:1;">
            <span style="color:#ffffff;">NeuroCarta</span><span style="color:#FFC107;font-weight:900;">.ai</span><span style="vertical-align:super;font-size:10px;color:rgba(255,255,255,.70);">®</span>
        </span>
    </a>

    @php
        $user            = auth()->user();
        $account         = $user ? $user->accounts()->first() : null;
        $sub             = $account ? $account->subscriptions()->latest()->first() : null;
        $currentPlan     = $sub?->plan_code ?? '';
        $currentInterval = $sub?->billing_interval ?? 'monthly';
        $hasStripe       = $sub && $sub->stripe_customer_id && $sub->stripe_subscription_id;
        $periodEnd       = $sub?->current_period_end_at;
        $planLabels      = ['basico' => 'Básico', 'pro' => 'Pro', 'premium' => 'Premium', 'trial' => 'Trial'];
        $intervalLabel   = $currentInterval === 'annual' ? 'anual' : 'mensual';
    @endphp

    @if(session('status'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);max-width:900px;width:100%;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,255,255,.65);">{{ session('status') }}</p>
        </div>
    @endif
    @if($errors->has('portal'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(197,36,57,.12);border:1px solid rgba(197,36,57,.35);max-width:900px;width:100%;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,120,120,.90);">{{ $errors->first('portal') }}</p>
        </div>
    @endif

    <div style="width:100%;max-width:900px;margin-bottom:24px;">
        <h1 style="margin:0 0 4px;font-size:26px;font-weight:900;letter-spacing:-0.02em;">Cambia tu plan</h1>
        <p style="margin:0;font-size:14px;color:rgba(255,255,255,.45);">
            Plan actual: <strong style="color:rgba(255,255,255,.75);">{{ $planLabels[$currentPlan] ?? 'Sin plan' }} {{ $intervalLabel }}</strong>
            @if($periodEnd) · <span>Próxima renovación {{ $periodEnd->format('d M Y') }}</span> @endif
        </p>
    </div>

    @php
        $plans = [
            'basico' => [
                'label'    => 'Básico',
                'monthly'  => '25€/mes',
                'annual'   => '275€/año',
                'features' => ['70 productos · 6 categorías', '1 restaurante', 'Carta pública + QR'],
                'note'     => 'Sin IA ni traducciones',
                'btn'      => 'rgba(255,255,255,.12)',
                'btnHover' => 'rgba(255,255,255,.20)',
                'btnText'  => '#fff',
                'border'   => 'rgba(255,255,255,.10)',
            ],
            'pro' => [
                'label'    => 'Pro',
                'monthly'  => '35€/mes',
                'annual'   => '385€/año',
                'features' => ['250 productos · 15 categorías', '2 restaurantes', 'IA + traducciones + CSV', '200 créditos IA/mes'],
                'note'     => 'Más popular',
                'btn'      => '#c52439',
                'btnHover' => '#a81e2e',
                'btnText'  => '#fff',
                'border'   => 'rgba(197,36,57,.35)',
            ],
            'premium' => [
                'label'    => 'Premium',
                'monthly'  => '69€/mes',
                'annual'   => '759€/año',
                'features' => ['1.000 productos · 100 categorías', '3 restaurantes', 'IA + traducciones + CSV', '400 créditos IA/mes'],
                'note'     => 'Soporte preferente',
                'btn'      => 'rgba(255,193,7,.20)',
                'btnHover' => 'rgba(255,193,7,.35)',
                'btnText'  => '#FFC107',
                'border'   => 'rgba(255,193,7,.25)',
            ],
        ];
    @endphp

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;width:100%;max-width:900px;">
        @foreach($plans as $planKey => $plan)
        @php $isCurrent = $currentPlan === $planKey; @endphp
        <div style="border:1px solid {{ $isCurrent ? $plan['border'] : 'rgba(255,255,255,.08)' }};background:{{ $isCurrent ? 'rgba(255,255,255,.04)' : 'rgba(255,255,255,.02)' }};border-radius:16px;padding:24px;display:flex;flex-direction:column;position:relative;">

            @if($planKey === 'pro' && !$isCurrent)
            <div style="position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:#c52439;color:#fff;font-size:10px;font-weight:800;padding:3px 12px;border-radius:20px;white-space:nowrap;letter-spacing:.05em;">MÁS POPULAR</div>
            @endif

            @if($isCurrent)
            <div style="display:inline-block;margin-bottom:10px;font-size:10px;font-weight:800;background:rgba(255,255,255,.10);color:rgba(255,255,255,.50);padding:2px 8px;border-radius:20px;width:fit-content;letter-spacing:.05em;">PLAN ACTUAL</div>
            @endif

            <div style="font-size:18px;font-weight:800;color:#fff;margin-bottom:12px;">{{ $plan['label'] }}</div>

            <ul style="list-style:none;margin:0 0 20px;padding:0;display:flex;flex-direction:column;gap:6px;font-size:13px;color:rgba(255,255,255,.70);flex:1;">
                @foreach($plan['features'] as $feature)
                <li>✓ {{ $feature }}</li>
                @endforeach
                <li style="font-size:12px;color:rgba(255,255,255,.30);margin-top:2px;">{{ $plan['note'] }}</li>
            </ul>

            {{-- Botón mensual --}}
            @if($isCurrent && $currentInterval === 'monthly')
                <div style="padding:10px 14px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.35);">Mensual · actual</span>
                    <span style="font-size:13px;font-weight:800;color:rgba(255,255,255,.30);">{{ $plan['monthly'] }}</span>
                </div>
            @elseif($hasStripe)
                <form method="POST" action="{{ route('subscription.portal') }}" style="margin-bottom:8px;">
                    @csrf
                    <button type="submit"
                            style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);cursor:pointer;display:flex;justify-content:space-between;align-items:center;box-sizing:border-box;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.12)'" onmouseout="this.style.background='rgba(255,255,255,.06)'">
                        <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.60);">Mensual</span>
                        <span style="font-size:13px;font-weight:800;color:#fff;">{{ $plan['monthly'] }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('checkout.start', ['plan' => $planKey]) }}?interval=monthly"
                   style="padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);display:flex;justify-content:space-between;align-items:center;text-decoration:none;margin-bottom:8px;">
                    <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.60);">Mensual</span>
                    <span style="font-size:13px;font-weight:800;color:#fff;">{{ $plan['monthly'] }}</span>
                </a>
            @endif

            {{-- Botón anual --}}
            @if($isCurrent && $currentInterval === 'annual')
                <div style="padding:10px 14px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;font-weight:700;color:rgba(255,255,255,.35);">Anual · actual</span>
                    <span style="font-size:13px;font-weight:800;color:rgba(255,255,255,.30);">{{ $plan['annual'] }}</span>
                </div>
            @elseif($hasStripe)
                <form method="POST" action="{{ route('subscription.portal') }}">
                    @csrf
                    <button type="submit"
                            style="width:100%;padding:10px 14px;border-radius:10px;border:none;background:{{ $plan['btn'] }};cursor:pointer;display:flex;justify-content:space-between;align-items:center;box-sizing:border-box;transition:opacity .15s;"
                            onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                        <span style="font-size:12px;font-weight:700;color:{{ $plan['btnText'] }};">Anual &nbsp;<span style="font-size:10px;opacity:.7;background:rgba(0,0,0,.15);padding:1px 5px;border-radius:8px;">1 mes gratis</span></span>
                        <span style="font-size:13px;font-weight:800;color:{{ $plan['btnText'] }};">{{ $plan['annual'] }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('checkout.start', ['plan' => $planKey]) }}?interval=annual"
                   style="padding:10px 14px;border-radius:10px;background:{{ $plan['btn'] }};display:flex;justify-content:space-between;align-items:center;text-decoration:none;">
                    <span style="font-size:12px;font-weight:700;color:{{ $plan['btnText'] }};">Anual &nbsp;<span style="font-size:10px;opacity:.7;background:rgba(0,0,0,.15);padding:1px 5px;border-radius:8px;">1 mes gratis</span></span>
                    <span style="font-size:13px;font-weight:800;color:{{ $plan['btnText'] }};">{{ $plan['annual'] }}</span>
                </a>
            @endif

        </div>
        @endforeach
    </div>

    @if($hasStripe)
    <div style="margin-top:16px;max-width:900px;width:100%;text-align:center;">
        <p style="font-size:12px;color:rgba(255,255,255,.25);margin:0;">
            Mejoras inmediatas con prorrateo · Bajadas y cancelaciones al final del periodo pagado · Gestionado por Stripe
        </p>
    </div>
    @endif

    <div style="margin-top:28px;display:flex;gap:16px;align-items:center;">
        <a href="{{ route('product') }}" style="font-size:13px;color:rgba(255,255,255,.40);text-decoration:underline;">← Volver al panel</a>
        <span style="color:rgba(255,255,255,.15);">·</span>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:13px;color:rgba(255,255,255,.35);text-decoration:underline;padding:0;">Cerrar sesión</button>
        </form>
    </div>

</div>
</x-guest-layout>
