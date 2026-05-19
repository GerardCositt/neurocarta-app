<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Redirigiendo al pago…</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: system-ui, sans-serif; background: #f9fafb; color: #374151; }
        .spinner { width: 2rem; height: 2rem; border: 3px solid #e5e7eb; border-top-color: #4f46e5; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { text-align: center; font-size: .95rem; }
    </style>
</head>
<body>
    <div>
        <div class="spinner"></div>
        <p>Preparando tu pago, un momento…</p>
        <form id="f" method="POST" action="{{ route('checkout.create') }}">
            @csrf
            <input type="hidden" name="plan"     value="{{ $plan }}">
            <input type="hidden" name="interval" value="{{ $interval }}">
        </form>
    </div>
    <script>document.getElementById('f').submit();</script>
</body>
</html>
