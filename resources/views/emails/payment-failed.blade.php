<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problema con tu pago</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 40px 16px; }
        .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .card-header { background: #111827; padding: 28px 32px; }
        .brand { font-size: 22px; font-weight: 900; letter-spacing: -0.02em; color: #ffffff; }
        .brand .dot-ai { color: #FF7A00; }
        .brand sup { font-size: 9px; color: rgba(255,255,255,.50); vertical-align: super; }
        .card-body { padding: 32px; }
        .badge { display: inline-block; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; font-size: 12px; font-weight: 600; padding: 3px 10px; margin-bottom: 16px; }
        h1 { margin: 0 0 12px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; color: #111827; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #4b5563; }
        .alert-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 16px 18px; margin: 20px 0; font-size: 14px; color: #7f1d1d; line-height: 1.6; }
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
                    escríbenos a <a href="mailto:hola@neurocarta.ai" style="color:#FF7A00;">hola@neurocarta.ai</a>
                    y te ayudamos a solucionarlo.
                </p>

                <hr class="divider">

                <p style="font-size:13px;color:#9ca3af;margin:0;">
                    Si ya has actualizado tu método de pago, Stripe intentará el cobro automáticamente en las próximas horas.
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
