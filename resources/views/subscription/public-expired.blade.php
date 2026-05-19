<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Carta no disponible{{ isset($restaurant) ? ' — ' . $restaurant->name : '' }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            background: #0f1117;
            color: #fff;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        .icon {
            font-size: 56px;
            margin-bottom: 24px;
            line-height: 1;
        }

        .card {
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 16px;
            color: #fff;
        }

        .subtitle {
            font-size: 15px;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.55);
            margin-bottom: 32px;
        }

        .divider {
            width: 40px;
            height: 2px;
            background: rgba(255, 255, 255, 0.12);
            margin: 0 auto 28px;
            border-radius: 2px;
        }

        .owner-notice {
            background: rgba(255, 193, 7, 0.06);
            border: 1px solid rgba(255, 193, 7, 0.20);
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255, 193, 7, 0.85);
        }

        .owner-notice a {
            color: #FFC107;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .restaurant-name {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.30);
            margin-top: 40px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        @media (max-width: 480px) {
            h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

    <div class="card">

        <div class="icon">🔒</div>

        <h1>Esta carta digital no está disponible en este momento</h1>

        <p class="subtitle">
            Lo sentimos, la carta digital de
            @if(isset($restaurant))
                <strong style="color:rgba(255,255,255,.75);">{{ $restaurant->name }}</strong>
            @else
                este restaurante
            @endif
            no está activa temporalmente.
        </p>

        <div class="divider"></div>

        <div class="owner-notice">
            <strong style="display:block;margin-bottom:6px;color:rgba(255,193,7,1);">¿Eres el propietario?</strong>
            Tu suscripción ha expirado o está inactiva. Reactiva tu cuenta en
            <a href="https://app.neurocarta.ai" target="_blank" rel="noopener">app.neurocarta.ai</a>
            o escríbenos a
            <a href="mailto:hola@neurocarta.ai">hola@neurocarta.ai</a>
            para que te ayudemos a reactivarla de inmediato.
        </div>

        @if(isset($restaurant) && $restaurant->name)
            <p class="restaurant-name">{{ $restaurant->name }}</p>
        @endif

    </div>

</body>
</html>
