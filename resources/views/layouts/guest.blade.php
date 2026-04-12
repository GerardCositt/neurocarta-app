<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>NeuroCarta.ai® · Acceso</title>

        <!-- Fonts (igual que la landing) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

        <!-- Scripts -->
        <script src="{{ mix('js/app.js') }}" defer></script>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <style>
            :root { color-scheme: dark; }
            body {
                margin: 0;
                background: #0F0F0F;
                color: #ffffff;
                font-family: "DM Sans", ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
            .nc-bg-glow {
                position: fixed;
                inset: 0;
                pointer-events: none;
                background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(197,36,57,0.18), transparent);
            }
        </style>
    </head>
    <body>
        <div class="min-h-screen">
            <div class="nc-bg-glow"></div>
            {{ $slot }}
        </div>
    </body>
</html>
