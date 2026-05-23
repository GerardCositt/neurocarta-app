<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu prueba gratuita termina pronto</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 40px 16px; }
        .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .card-header { background: #111827; padding: 28px 32px; }
        .brand { font-size: 22px; font-weight: 900; letter-spacing: -0.02em; color: #ffffff; }
        .brand .dot-ai { color: #FF7A00; }
        .brand sup { font-size: 9px; color: rgba(255,255,255,.50); vertical-align: super; }
        .card-body { padding: 32px; }
        .badge { display: inline-block; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; border-radius: 6px; font-size: 12px; font-weight: 600; padding: 3px 10px; margin-bottom: 16px; }
        h1 { margin: 0 0 12px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; color: #111827; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #4b5563; }
        .btn-wrap { margin: 24px 0; }
        .btn { display: inline-block; padding: 14px 32px; background: #FF7A00; color: #ffffff; text-decoration: none; border-radius: 10px; font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
        .divider { border: none; border-top: 1px solid #f0f0f0; margin: 24px 0; }
        .plan-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .plan-row:last-child { border-bottom: none; }
        .plan-name { font-weight: 700; color: #111827; }
        .plan-price { color: #6b7280; }
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
                @if($daysLeft > 0)
                    <div class="badge">⏰ {{ $daysLeft }} {{ $daysLeft === 1 ? 'día restante' : 'días restantes' }}</div>
                    <h1>Tu prueba termina en {{ $daysLeft }} {{ $daysLeft === 1 ? 'día' : 'días' }}</h1>
                    <p>
                        Hola <span class="highlight">{{ $user->name }}</span>, tu prueba gratuita de NeuroCarta.ai® expira en
                        <span class="highlight">{{ $daysLeft }} {{ $daysLeft === 1 ? 'día' : 'días' }}</span>.
                        No pierdas acceso a tu carta digital.
                    </p>
                @else
                    <div class="badge">⏰ Último día</div>
                    <h1>Hoy es el último día de tu prueba</h1>
                    <p>
                        Hola <span class="highlight">{{ $user->name }}</span>, hoy termina tu prueba gratuita de NeuroCarta.ai®.
                        A partir de mañana necesitarás un plan activo para acceder al panel y a tu carta pública.
                    </p>
                @endif

                <p>Elige el plan que mejor se adapte a tu restaurante y sigue sin interrupciones:</p>

                <div style="margin: 20px 0; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
                    <div class="plan-row" style="padding: 11px 16px;">
                        <span class="plan-name">Básico</span>
                        <span class="plan-price">25€/mes · 70 productos</span>
                    </div>
                    <div class="plan-row" style="padding: 11px 16px;">
                        <span class="plan-name">Pro ⭐</span>
                        <span class="plan-price">35€/mes · 250 productos + IA</span>
                    </div>
                    <div class="plan-row" style="padding: 11px 16px; border-bottom: none;">
                        <span class="plan-name">Premium</span>
                        <span class="plan-price">69€/mes · 1.000 productos + IA</span>
                    </div>
                </div>

                <div class="btn-wrap">
                    <a href="{{ config('app.url') }}/subscription/expired" class="btn">Ver planes y continuar →</a>
                </div>

                <hr class="divider">

                <p style="font-size:13px;color:#9ca3af;margin:0;">
                    ¿Tienes preguntas? Escríbenos a
                    <a href="mailto:hola@neurocarta.ai" style="color:#6b7280;">hola@neurocarta.ai</a>
                    y te ayudamos a elegir el plan adecuado.
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
