<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu prueba gratuita termina pronto</title>
    <style>
        body { margin: 0; padding: 0; background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #ffffff; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 48px 24px; }
        .brand { font-size: 26px; font-weight: 900; letter-spacing: -0.02em; margin-bottom: 32px; }
        .brand .ai { color: #FFC107; }
        .brand sup { font-size: 10px; color: rgba(255,255,255,.60); vertical-align: super; }
        .card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.10); border-radius: 16px; padding: 32px; }
        .badge { display: inline-block; background: rgba(255,193,7,.15); color: #FFC107; font-size: 13px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 20px; }
        h1 { margin: 0 0 12px; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: rgba(255,255,255,.72); }
        .btn { display: inline-block; padding: 14px 28px; background: #c52439; color: #ffffff; text-decoration: none; border-radius: 12px; font-size: 16px; font-weight: 800; letter-spacing: -0.01em; margin: 8px 0 24px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: 24px 0; }
        .plan-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,.06); font-size: 14px; }
        .plan-row:last-child { border-bottom: none; }
        .plan-name { font-weight: 700; color: #fff; }
        .plan-price { color: rgba(255,255,255,.55); }
        .footer { margin-top: 32px; font-size: 12px; color: rgba(255,255,255,.30); text-align: center; line-height: 1.6; }
        .footer a { color: rgba(255,255,255,.45); text-decoration: underline; }
        .highlight { color: #ffffff; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand">NeuroCarta<span class="ai">.ai</span><sup>®</sup></div>

        <div class="card">
            @if($daysLeft > 0)
                <div class="badge">⏰ {{ $daysLeft }} {{ $daysLeft === 1 ? 'día' : 'días' }} restantes</div>
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

            <div style="margin: 20px 0;">
                <div class="plan-row">
                    <span class="plan-name">Básico</span>
                    <span class="plan-price">59€/mes · 100 productos</span>
                </div>
                <div class="plan-row">
                    <span class="plan-name">Pro ⭐</span>
                    <span class="plan-price">129€/mes · 500 productos + IA</span>
                </div>
                <div class="plan-row">
                    <span class="plan-name">Premium</span>
                    <span class="plan-price">249€/mes · 2.000 productos + IA ilimitada</span>
                </div>
            </div>

            <a href="{{ config('app.url') }}/subscription/expired" class="btn">
                Ver planes y continuar
            </a>

            <hr class="divider">

            <p style="font-size:13px;color:rgba(255,255,255,.45);margin:0;">
                ¿Tienes preguntas? Escríbenos a
                <a href="mailto:hola@neurocarta.ai" style="color:rgba(255,255,255,.65);">hola@neurocarta.ai</a>
                y te ayudamos a elegir el plan adecuado.
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
