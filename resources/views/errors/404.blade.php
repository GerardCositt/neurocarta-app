<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Plato no disponible · NeuroCarta.ai</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: #0f1117;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #f9fafb;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .card {
            max-width: 480px;
            width: 100%;
            background: #1a1d27;
            border: 1px solid #2d3148;
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
        }
        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 36px;
        }
        .logo-wrap img { width: 36px; height: 36px; }
        .brand { font-size: 18px; font-weight: 800; letter-spacing: -0.02em; }
        .brand span { color: #FF7A00; }
        .code {
            font-size: 96px;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
            color: #FF7A00;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        p {
            font-size: 15px;
            color: #9ca3af;
            line-height: 1.65;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background: #FF7A00;
            color: #fff;
            text-decoration: none;
            padding: 13px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.01em;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .88; }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-wrap">
            <img src="/img/butterfly.svg" alt="NeuroCarta.ai">
            <span class="brand">NeuroCarta<span>.ai</span></span>
        </div>

        <div class="code">404</div>
        <h1>Este plato no está en la carta</h1>
        <p>
            Lo sentimos, la página que buscas no existe o ha sido retirada del menú.<br>
            Puede que el cocinero la haya cambiado de sitio.
        </p>

        <a href="/" class="btn">← Volver al inicio</a>
    </div>

    <div class="footer">© {{ date('Y') }} NeuroCarta.ai® · Cositt</div>
</body>
</html>
