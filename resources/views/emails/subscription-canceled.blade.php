<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción cancelada</title>
    <style>
        body { margin: 0; padding: 0; background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #ffffff; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 48px 24px; }
        .brand { font-size: 26px; font-weight: 900; letter-spacing: -0.02em; margin-bottom: 32px; }
        .brand .ai { color: #FFC107; }
        .brand sup { font-size: 10px; color: rgba(255,255,255,.60); vertical-align: super; }
        .card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.10); border-radius: 16px; padding: 32px; }
        .badge { display: inline-block; background: rgba(255,255,255,.08); color: rgba(255,255,255,.60); font-size: 13px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 20px; }
        h1 { margin: 0 0 12px; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: rgba(255,255,255,.72); }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: 24px 0; }
        .info-box { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.10); border-radius: 10px; padding: 16px 18px; margin: 20px 0; font-size: 14px; color: rgba(255,255,255,.65); line-height: 1.6; }
        .footer { margin-top: 32px; font-size: 12px; color: rgba(255,255,255,.30); text-align: center; line-height: 1.6; }
        .footer a { color: rgba(255,255,255,.45); text-decoration: underline; }
        .highlight { color: #ffffff; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand">NeuroCarta<span class="ai">.ai</span><sup>®</sup></div>

        <div class="card">
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
                escríbenos a <a href="mailto:hola@neurocarta.ai" style="color:rgba(255,255,255,.75);">hola@neurocarta.ai</a>.
            </p>

            <hr class="divider">

            <p style="font-size:13px;color:rgba(255,255,255,.45);margin:0;">
                Tus datos y configuración se conservarán durante 30 días por si decides volver.
            </p>
        </div>

        <div class="footer">
            Has recibido este email porque tienes una cuenta en
            <a href="https://neurocarta.ai">neurocarta.ai</a>.<br><br>
            © {{ date('Y') }} NeuroCarta.ai® · <a href="mailto:hola@neurocarta.ai">hola@neurocarta.ai</a>
        </div>
    </div>
</body>
</html>
