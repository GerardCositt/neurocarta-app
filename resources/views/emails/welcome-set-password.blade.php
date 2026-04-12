<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activa tu cuenta en NeuroCarta.ai®</title>
    <style>
        body { margin: 0; padding: 0; background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #ffffff; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 48px 24px; }
        .brand { font-size: 26px; font-weight: 900; letter-spacing: -0.02em; margin-bottom: 32px; }
        .brand .ai { color: #FFC107; }
        .brand sup { font-size: 10px; color: rgba(255,255,255,.60); vertical-align: super; }
        .card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.10); border-radius: 16px; padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; }
        p { margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: rgba(255,255,255,.72); }
        .btn { display: inline-block; padding: 14px 28px; background: #FFC107; color: #0F0F0F; text-decoration: none; border-radius: 12px; font-size: 16px; font-weight: 800; letter-spacing: -0.01em; margin: 8px 0 24px; }
        .url-fallback { font-size: 12px; color: rgba(255,255,255,.35); word-break: break-all; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: 24px 0; }
        .footer { margin-top: 32px; font-size: 12px; color: rgba(255,255,255,.30); text-align: center; line-height: 1.6; }
        .footer a { color: rgba(255,255,255,.45); text-decoration: underline; }
        .highlight { color: #ffffff; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand">NeuroCarta<span class="ai">.ai</span><sup>®</sup></div>

        <div class="card">
            <h1>¡Bienvenido, {{ $user->name }}!</h1>
            <p>
                Tu restaurante <span class="highlight">{{ $user->name }}</span> ya está registrado en NeuroCarta.ai®.
                Solo falta un paso: crea tu contraseña para activar la cuenta.
            </p>
            <p>
                El enlace es válido <span class="highlight">3 días</span>. Si lo pierdes, puedes solicitar uno nuevo desde el login.
            </p>

            <a href="{{ $setPasswordUrl }}" class="btn">
                Crear contraseña y activar cuenta
            </a>

            <hr class="divider">

            <p style="font-size:13px;margin-bottom:6px;">¿El botón no funciona? Copia este enlace en tu navegador:</p>
            <p class="url-fallback">{{ $setPasswordUrl }}</p>
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
