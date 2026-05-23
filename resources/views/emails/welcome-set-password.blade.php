<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activa tu cuenta en NeuroCarta.ai®</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 40px 16px; }
        .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .card-header { background: #111827; padding: 28px 32px; }
        .brand { font-size: 22px; font-weight: 900; letter-spacing: -0.02em; color: #ffffff; }
        .brand .dot-ai { color: #FF7A00; }
        .brand sup { font-size: 9px; color: rgba(255,255,255,.50); vertical-align: super; }
        .card-body { padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; color: #111827; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #4b5563; }
        .btn-wrap { margin: 24px 0; }
        .btn { display: inline-block; padding: 14px 32px; background: #FF7A00; color: #ffffff; text-decoration: none; border-radius: 10px; font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
        .divider { border: none; border-top: 1px solid #f0f0f0; margin: 24px 0; }
        .url-label { font-size: 12px; color: #9ca3af; margin-bottom: 4px; }
        .url-fallback { font-size: 12px; color: #6b7280; word-break: break-all; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; text-align: center; line-height: 1.7; }
        .footer a { color: #6b7280; text-decoration: underline; }
        .highlight { color: #111827; font-weight: 600; }
        .badge { display: inline-block; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; border-radius: 6px; font-size: 12px; font-weight: 600; padding: 2px 8px; vertical-align: middle; }
    </style>
</head>
<body>
    <div class="wrapper">

        <div class="card">
            <div class="card-header">
                <div class="brand">NeuroCarta<span class="dot-ai">.ai</span><sup>®</sup></div>
            </div>

            <div class="card-body">
                <h1>¡Bienvenido, {{ $user->name }}!</h1>
                <p>
                    Tu restaurante <span class="highlight">{{ $user->name }}</span> ya está registrado en NeuroCarta.ai®.
                    Solo falta un paso: crea tu contraseña para activar la cuenta.
                </p>
                <p>
                    El enlace es válido <span class="badge">3 días</span>. Si lo pierdes, puedes solicitar uno nuevo desde el login.
                </p>

                <div class="btn-wrap">
                    <a href="{{ $setPasswordUrl }}" class="btn">Crear contraseña y activar cuenta →</a>
                </div>

                <hr class="divider">

                <p class="url-label">¿El botón no funciona? Copia este enlace en tu navegador:</p>
                <p class="url-fallback">{{ $setPasswordUrl }}</p>
            </div>
        </div>

        <div class="footer">
            Has recibido este email porque alguien se registró con esta dirección en
            <a href="https://neurocarta.ai">neurocarta.ai</a>.<br>
            Si no fuiste tú, ignora este mensaje.<br><br>
            © {{ date('Y') }} NeuroCarta.ai® · <a href="mailto:hola@neurocarta.ai">hola@neurocarta.ai</a>
        </div>

    </div>
</body>
</html>
