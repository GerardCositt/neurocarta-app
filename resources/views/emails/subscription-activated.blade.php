<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu suscripción está activa</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 40px 16px; }
        .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .card-header { background: #111827; padding: 28px 32px; }
        .brand { font-size: 22px; font-weight: 900; letter-spacing: -0.02em; color: #ffffff; }
        .brand .dot-ai { color: #FF7A00; }
        .brand sup { font-size: 9px; color: rgba(255,255,255,.50); vertical-align: super; }
        .card-body { padding: 32px; }
        .badge { display: inline-block; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 12px; font-weight: 600; padding: 3px 10px; margin-bottom: 16px; }
        h1 { margin: 0 0 12px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; color: #111827; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #4b5563; }
        .btn-wrap { margin: 24px 0; }
        .btn { display: inline-block; padding: 14px 32px; background: #FF7A00; color: #ffffff; text-decoration: none; border-radius: 10px; font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
        .divider { border: none; border-top: 1px solid #f0f0f0; margin: 24px 0; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: 600; color: #111827; }
        .highlight { color: #111827; font-weight: 600; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; text-align: center; line-height: 1.7; }
        .footer a { color: #6b7280; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="card-header">
                <div class="brand">NeuroCarta<span class="dot-ai">.ai</span><sup>®</sup></div>
                <div style="margin-top:20px;text-align:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="80" height="80" role="img" aria-label="NeuroCarta mascot">
                        <path d="M48,44 C38,16 5,12 5,34 C5,54 26,62 48,60" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M52,44 C62,16 95,12 95,34 C95,54 74,62 52,60" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M48,61 C35,65 8,74 8,86 C8,95 32,92 48,72" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M52,61 C65,65 92,74 92,86 C92,95 68,92 52,72" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="30" cy="40" r="5.5" fill="#FFC107" opacity="0.55"/>
                        <circle cx="70" cy="40" r="5.5" fill="#FFC107" opacity="0.55"/>
                        <circle cx="25" cy="76" r="4" fill="#FFC107" opacity="0.4"/>
                        <circle cx="75" cy="76" r="4" fill="#FFC107" opacity="0.4"/>
                        <ellipse cx="50" cy="58" rx="3.5" ry="15" fill="#1D1D1B"/>
                        <circle cx="50" cy="42" r="3.5" fill="#1D1D1B"/>
                        <path d="M48,39 Q40,25 34,18" fill="none" stroke="#1D1D1B" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="34" cy="18" r="2.5" fill="#1D1D1B"/>
                        <path d="M52,39 Q60,25 66,18" fill="none" stroke="#1D1D1B" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="66" cy="18" r="2.5" fill="#1D1D1B"/>
                    </svg>
                </div>
            </div>

            <div class="card-body">
                <div class="badge">✅ Suscripción activa</div>
                <h1>Tu suscripción está activa</h1>
                <p>
                    Hola <span class="highlight">{{ $user->name }}</span>,
                    tu suscripción a NeuroCarta.ai® ha quedado activada. Ya puedes usar el panel y tu carta digital.
                </p>

                @php
                    $planLabel     = match($subscription->plan_code) { 'basico' => 'Básico', 'pro' => 'Pro', 'premium' => 'Premium', default => ucfirst($subscription->plan_code) };
                    $intervalLabel = $subscription->billing_interval === 'annual' ? 'Anual' : 'Mensual';
                @endphp

                <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin: 20px 0;">
                    <div class="detail-row">
                        <span class="detail-label">Plan</span>
                        <span class="detail-value">{{ $planLabel }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Facturación</span>
                        <span class="detail-value">{{ $intervalLabel }}</span>
                    </div>
                    @if($subscription->current_period_end_at)
                    <div class="detail-row">
                        <span class="detail-label">Próxima renovación</span>
                        <span class="detail-value">{{ $subscription->current_period_end_at->format('d/m/Y') }}</span>
                    </div>
                    @endif
                </div>

                <div class="btn-wrap">
                    <a href="{{ config('app.url') }}/product" class="btn">Ir al panel →</a>
                </div>

                <hr class="divider">

                <p style="font-size:13px;color:#9ca3af;margin:0;">
                    ¿Tienes alguna pregunta? Escríbenos a
                    <a href="mailto:hola@neurocarta.ai" style="color:#6b7280;">hola@neurocarta.ai</a>.
                </p>
            </div>
        </div>

        <div class="footer">
            Has recibido este email porque tienes una cuenta en
            <a href="https://neurocarta.ai">neurocarta.ai</a>.<br><br>
            © {{ date('Y') }} NeuroCarta.ai® · <a href="mailto:hola@neurocarta.ai">hola@neurocarta.ai</a>
        </div>
    </div>
</body>
</html>
