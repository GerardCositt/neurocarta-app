<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovación confirmada</title>
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
            </div>

            <div class="card-body">
                <div class="badge">✅ Pago confirmado</div>
                <h1>Tu renovación ha sido confirmada</h1>
                <p>
                    Hola <span class="highlight">{{ $user->name }}</span>,
                    hemos procesado correctamente el pago de tu suscripción a NeuroCarta.ai®.
                    Tu acceso sigue activo sin interrupciones.
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
