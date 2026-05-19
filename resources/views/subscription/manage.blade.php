<x-guest-layout>
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:56px 20px;">

    {{-- Logo --}}
    <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;margin-bottom:40px;">
        <span style="font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;font-size:32px;font-weight:900;letter-spacing:-0.02em;line-height:1;">
            <span style="color:#ffffff;">NeuroCarta</span><span style="color:#FFC107;font-weight:900;">.ai</span><span style="vertical-align:super;font-size:10px;color:rgba(255,255,255,.70);">®</span>
        </span>
    </a>

    @php
        $user    = auth()->user();
        $account = $user ? $user->accounts()->first() : null;
        $sub     = $account ? $account->subscriptions()->latest()->first() : null;

        $planLabels = [
            'basico'  => 'Básico',
            'pro'     => 'Pro',
            'premium' => 'Premium',
            'trial'   => 'Trial',
        ];
        $planLabel    = $planLabels[$sub?->plan_code ?? ''] ?? 'Sin plan';
        $hasStripe    = $sub && $sub->stripe_customer_id;
        $intervalLabel = match($sub?->billing_interval ?? '') {
            'annual'  => 'anual',
            'monthly' => 'mensual',
            default   => '',
        };
        $periodEnd = $sub?->current_period_end_at;
    @endphp

    {{-- Status message --}}
    @if(session('status'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);max-width:500px;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,255,255,.65);">{{ session('status') }}</p>
        </div>
    @endif

    @if($errors->has('portal'))
        <div style="margin-bottom:20px;padding:12px 18px;border-radius:10px;background:rgba(197,36,57,.12);border:1px solid rgba(197,36,57,.35);max-width:500px;text-align:center;">
            <p style="margin:0;font-size:13px;color:rgba(255,120,120,.90);">{{ $errors->first('portal') }}</p>
        </div>
    @endif

    {{-- Card --}}
    <div style="width:100%;max-width:480px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.10);border-radius:20px;padding:36px;">

        <h1 style="margin:0 0 6px;font-size:26px;font-weight:900;letter-spacing:-0.02em;">Gestionar suscripción</h1>
        <p style="margin:0 0 28px;font-size:15px;color:rgba(255,255,255,.55);">
            Cambia de plan, actualiza tu tarjeta o cancela desde el portal seguro de Stripe.
        </p>

        {{-- Current plan info --}}
        @if($sub)
        <div style="background:rgba(255,255,255,.06);border-radius:12px;padding:18px 20px;margin-bottom:24px;">
            <div style="font-size:12px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Plan actual</div>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:22px;font-weight:800;">{{ $planLabel }}</span>
                @if($intervalLabel)
                    <span style="font-size:12px;background:rgba(255,255,255,.08);padding:4px 10px;border-radius:20px;color:rgba(255,255,255,.60);">{{ $intervalLabel }}</span>
                @endif
            </div>
            @if($periodEnd)
            <div style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.40);">
                Próxima renovación: {{ $periodEnd->format('d M Y') }}
            </div>
            @endif
        </div>
        @endif

        {{-- Portal button (only if stripe customer exists) --}}
        @if($hasStripe)
        <form method="POST" action="{{ route('subscription.portal') }}">
            @csrf
            <button type="submit"
                    style="width:100%;padding:14px;border-radius:12px;border:none;cursor:pointer;font-size:15px;font-weight:800;letter-spacing:-0.01em;background:#c52439;color:#fff;">
                Abrir portal de facturación →
            </button>
        </form>
        <p style="margin:12px 0 0;font-size:12px;color:rgba(255,255,255,.30);text-align:center;">
            Serás redirigido al portal seguro de Stripe. Puedes volver aquí en cualquier momento.
        </p>
        @else
        {{-- No stripe customer: first payment hasn't been completed --}}
        <a href="{{ route('subscription.expired') }}"
           style="display:block;width:100%;box-sizing:border-box;padding:14px;border-radius:12px;text-align:center;text-decoration:none;font-size:15px;font-weight:800;letter-spacing:-0.01em;background:#FFC107;color:#0F0F0F;">
            Activar suscripción →
        </a>
        <p style="margin:12px 0 0;font-size:12px;color:rgba(255,255,255,.30);text-align:center;">
            Completa tu primer pago para acceder al portal de gestión.
        </p>
        @endif

    </div>

    {{-- Back link --}}
    <div style="margin-top:28px;text-align:center;font-size:13px;color:rgba(255,255,255,.35);">
        <a href="{{ route('product.index') }}" style="color:rgba(255,255,255,.55);text-decoration:underline;">← Volver al panel</a>
    </div>

</div>
</x-guest-layout>
