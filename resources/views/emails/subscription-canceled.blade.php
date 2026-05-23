<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción cancelada</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 40px 16px; }
        .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .card-header { background: #111827; padding: 28px 32px; }
        .brand { font-size: 22px; font-weight: 900; letter-spacing: -0.02em; color: #ffffff; }
        .brand .dot-ai { color: #FF7A00; }
        .brand sup { font-size: 9px; color: rgba(255,255,255,.50); vertical-align: super; }
        .card-body { padding: 32px; }
        .badge { display: inline-block; background: #f9fafb; color: #374151; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; font-weight: 600; padding: 3px 10px; margin-bottom: 16px; }
        h1 { margin: 0 0 12px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; color: #111827; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #4b5563; }
        .info-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; margin: 20px 0; font-size: 14px; color: #374151; line-height: 1.6; }
        .divider { border: none; border-top: 1px solid #f0f0f0; margin: 24px 0; }
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
                <div class="badge">ℹ️ Suscripción cancelada</div>
                <h1>Tu suscripción ha sido cancelada</h1>
                <p>
                    Hola <span class="highlight">{{ $user->name }}</span>,
                    tu suscripción a NeuroCarta.ai® ha quedado cancelada.
                </p>

                @if($subscription->current_period_end_at && $subscription->current_period_end_at->isFuture())
                <div class="info-box">
                    Tu acceso permanecerá activo hasta el
                    <strong>{{ $subscription->current_period_end_at->format('d/m/Y') }}</strong>.
                    A partir de esa fecha tu carta y panel quedarán desactivados.
                </div>
                @else
                <div class="info-box">
                    Tu acceso ha quedado desactivado. Tu carta y panel ya no están disponibles.
                </div>
                @endif

                <p>
                    Si crees que esto es un error o quieres reactivar tu cuenta,
                    escríbenos a <a href="mailto:hola@neurocarta.ai" style="color:#FF7A00;">hola@neurocarta.ai</a>.
                </p>

                <hr class="divider">

                <p style="font-size:13px;color:#9ca3af;margin:0;">
                    Tus datos y configuración se conservarán durante 30 días por si decides volver.
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
