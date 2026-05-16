<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problema con tu pago</title>
    <style>
        body { margin: 0; padding: 0; background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #ffffff; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 48px 24px; }
        .brand { font-size: 26px; font-weight: 900; letter-spacing: -0.02em; margin-bottom: 32px; }
        .brand .ai { color: #FFC107; }
        .brand sup { font-size: 10px; color: rgba(255,255,255,.60); vertical-align: super; }
        .card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.10); border-radius: 16px; padding: 32px; }
        .badge { display: inline-block; background: rgba(197,36,57,.15); color: #f87171; font-size: 13px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 20px; }
        h1 { margin: 0 0 12px; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: rgba(255,255,255,.72); }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: 24px 0; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.06); font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: rgba(255,255,255,.50); }
        .detail-value { font-weight: 600; color: #fff; }
        .alert-box { background: rgba(197,36,57,.10); border: 1px solid rgba(197,36,57,.30); border-radius: 10px; padding: 16px 18px; margin: 20px 0; font-size: 14px; color: rgba(255,200,200,.85); line-height: 1.6; }
        .footer { margin-top: 32px; font-size: 12px; color: rgba(255,255,255,.30); text-align: center; line-height: 1.6; }
        .footer a { color: rgba(255,255,255,.45); text-decoration: underline; }
        .highlight { color: #ffffff; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand">NeuroCarta<span class="ai">.ai</span><sup>®</sup></div>

        <div class="card">
            <div class="badge">⚠️ Pago fallido</div>
            <h1>No hemos podido cobrar tu suscripción</h1>
            <p>
                Hola <span class="highlight">{{ $user->name }}</span>,
                ha habido un problema al procesar el pago de tu suscripción a NeuroCarta.ai®.
            </p>

            <div class="alert-box">
                Tienes un período de gracia de <strong>4 días</strong> para actualizar tu método de pago.
                Durante este tiempo tu acceso sigue activo.
                @if($subscription->grace_period_ends_at)
                    <br><br>
                    Tu acceso expira el <strong>{{ $subscription->grace_period_ends_at->format('d/m/Y') }}</strong>.
                @endif
            </div>

            <p>
                Para actualizar tu método de pago o resolver el problema,
                escríbenos a <a href="mailto:hola@neurocarta.ai" style="color:#f87171;">hola@neurocarta.ai</a>
                y te ayudamos a solucionarlo.
            </p>

            <hr class="divider">

            <p style="font-size:13px;color:rgba(255,255,255,.45);margin:0;">
                Si ya has actualizado tu método de pago, Stripe intentará el cobro automáticamente en las próximas horas.
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
