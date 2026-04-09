<!DOCTYPE html>
<html lang="{{ $locale ?? 'es' }}">
<head>
    <meta charset="UTF-8"/>
    <script>
    (function () {
        try {
            var k = 'bjCartaTheme';
            var t = localStorage.getItem(k) || 'light';
            if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $restaurant->name ?? config('app.name') }} · {{ __('public_menu.page_title_suffix') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0e17;
            --surface: #111827;
            --surface-el: #1a2235;
            --gold: #c9a84c;
            --gold-light: #dfc06e;
            --gold-dim: #8a6d2b;
            --text: #e8e4dc;
            --text-muted: #9ca3af;
            --red: #dc2626;
            --font-title: 'Playfair Display', Georgia, serif;
            --font-body: 'Montserrat', system-ui, sans-serif;
            --radius: 12px;
            --nav-bg: rgba(10, 14, 23, 0.95);
            --nav-border: rgba(201, 168, 76, 0.12);
            --hero-grad-from: #111827;
            --nav-active-fg: #0a0e17;
            --modal-scrim: rgba(10, 14, 23, 0.48);
            --prod-border: rgba(255, 255, 255, 0.06);
            --divider: rgba(255, 255, 255, 0.08);
            --chip-bg: rgba(255, 255, 255, 0.04);
            --modal-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            --scroll-top-fg: #0a0e17;
            --hero-glow: rgba(201, 168, 76, 0.14);
            --gold-border-soft: rgba(201, 168, 76, 0.35);
            --gold-border-hover: rgba(201, 168, 76, 0.52);
            --gold-focus-ring: rgba(201, 168, 76, 0.24);
        }

        html[data-theme="light"] {
            color-scheme: light;
            --bg: #faf8f5;
            --surface: #ffffff;
            --surface-el: #f3f0eb;
            --gold: #9a7328;
            --gold-light: #b8923a;
            --gold-dim: #7a5a1e;
            --text: #1c1917;
            --text-muted: #78716c;
            --red: #b91c1c;
            --nav-bg: rgba(255, 252, 248, 0.94);
            --nav-border: rgba(154, 115, 40, 0.18);
            --hero-grad-from: #ebe6dc;
            --nav-active-fg: #1c1917;
            --modal-scrim: rgba(28, 25, 23, 0.4);
            --prod-border: rgba(0, 0, 0, 0.07);
            --divider: rgba(0, 0, 0, 0.1);
            --chip-bg: rgba(0, 0, 0, 0.04);
            --modal-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            --scroll-top-fg: #1c1917;
            --hero-glow: rgba(154, 115, 40, 0.14);
            --gold-border-soft: rgba(154, 115, 40, 0.35);
            --gold-border-hover: rgba(154, 115, 40, 0.52);
            --gold-focus-ring: rgba(154, 115, 40, 0.24);
        }

        @media (prefers-color-scheme: light) {
            html[data-theme="system"] {
                color-scheme: light;
                --bg: #faf8f5;
                --surface: #ffffff;
                --surface-el: #f3f0eb;
                --gold: #9a7328;
                --gold-light: #b8923a;
                --gold-dim: #7a5a1e;
                --text: #1c1917;
                --text-muted: #78716c;
                --red: #b91c1c;
                --nav-bg: rgba(255, 252, 248, 0.94);
                --nav-border: rgba(154, 115, 40, 0.18);
                --hero-grad-from: #ebe6dc;
                --nav-active-fg: #1c1917;
                --modal-scrim: rgba(28, 25, 23, 0.4);
                --prod-border: rgba(0, 0, 0, 0.07);
                --divider: rgba(0, 0, 0, 0.1);
                --chip-bg: rgba(0, 0, 0, 0.04);
                --modal-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
                --scroll-top-fg: #1c1917;
                --hero-glow: rgba(154, 115, 40, 0.14);
                --gold-border-soft: rgba(154, 115, 40, 0.35);
                --gold-border-hover: rgba(154, 115, 40, 0.52);
                --gold-focus-ring: rgba(154, 115, 40, 0.24);
            }
        }

        @media (prefers-color-scheme: dark) {
            html[data-theme="system"] {
                color-scheme: dark;
                --bg: #0a0e17;
                --surface: #111827;
                --surface-el: #1a2235;
                --gold: #c9a84c;
                --gold-light: #dfc06e;
                --gold-dim: #8a6d2b;
                --text: #e8e4dc;
                --text-muted: #9ca3af;
                --red: #dc2626;
                --nav-bg: rgba(10, 14, 23, 0.95);
                --nav-border: rgba(201, 168, 76, 0.12);
                --hero-grad-from: #111827;
                --nav-active-fg: #0a0e17;
                --modal-scrim: rgba(10, 14, 23, 0.48);
                --prod-border: rgba(255, 255, 255, 0.06);
                --divider: rgba(255, 255, 255, 0.08);
                --chip-bg: rgba(255, 255, 255, 0.04);
                --modal-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
                --scroll-top-fg: #0a0e17;
                --hero-glow: rgba(201, 168, 76, 0.14);
                --gold-border-soft: rgba(201, 168, 76, 0.35);
                --gold-border-hover: rgba(201, 168, 76, 0.52);
                --gold-focus-ring: rgba(201, 168, 76, 0.24);
            }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* HERO */
        .hero {
            text-align: center;
            padding: 48px 20px 24px;
            background: linear-gradient(180deg, var(--hero-grad-from), var(--bg));
            position: relative;
        }
        .hero::before {
            content: ''; position: absolute; top: -60px; left: 50%;
            transform: translateX(-50%); width: 320px; height: 320px;
            background: radial-gradient(circle, var(--hero-glow), transparent 70%);
            pointer-events: none;
        }
        .hero-logo {
            width: 68px; height: 68px; border-radius: 50%;
            border: 2px solid var(--gold); margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
            background: var(--surface); font-family: var(--font-title);
            font-size: 22px; color: var(--gold); font-weight: 700;
            overflow: hidden;
        }
        .hero-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero h1 { font-family: var(--font-title); font-size: 26px; color: var(--gold); letter-spacing: 1px; }
        .hero p { font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; color: var(--text-muted); margin-top: 3px; }
        .hero-line { width: 50px; height: 1px; background: var(--gold-dim); margin: 18px auto 0; }

        /* AVISO BUTTON */
        .advice-toggle {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 14px; font-size: 11px; font-weight: 600;
            letter-spacing: 1px; text-transform: uppercase; color: var(--gold-dim);
            background: none; border: 1px solid var(--gold-dim);
            padding: 6px 14px; border-radius: 20px; cursor: pointer;
        }

        /* NAV STICKY */
        .nav {
            position: sticky; top: 0; z-index: 100;
            background: var(--nav-bg); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--nav-border);
        }
        .nav-inner {
            display: flex; overflow-x: auto; scrollbar-width: none;
            padding: 10px 12px; gap: 8px; align-items: center;
        }
        .nav-inner::-webkit-scrollbar { display: none; }
        .nav-tab {
            flex-shrink: 0; padding: 6px 16px; font-size: 11px; font-weight: 600;
            letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-muted);
            text-decoration: none; border: 1px solid rgba(156,163,175,0.3);
            border-radius: 20px; transition: all .25s; white-space: nowrap; cursor: pointer;
            background: transparent;
        }
        button.nav-tab {
            font-family: inherit; -webkit-appearance: none; appearance: none;
            margin: 0; line-height: normal;
        }
        .nav-tab--settings .nav-tab-settings-inner {
            display: inline-flex; align-items: center; gap: 6px;
        }
        .nav-tab--settings svg {
            flex-shrink: 0; opacity: 0.9;
        }
        .nav-tab:hover { color: var(--gold); border-color: var(--gold-border-hover); }
        .nav-tab.active {
            color: var(--nav-active-fg); background: var(--gold);
            border-color: var(--gold); font-weight: 700;
        }
        .nav-tab--offer {
            color: var(--red); border-color: rgba(220,38,38,0.4); font-weight: 700;
        }
        .nav-tab--offer:hover { border-color: var(--red); }
        .nav-tab--offer.active {
            color: #fff; background: var(--red); border-color: var(--red);
        }

        /* Filtros carta (oferta / destacado / recomendado / lista) */
        .menu-filter-bar {
            background: var(--surface-el);
            border-bottom: 1px solid var(--divider);
            padding: 12px 14px 14px;
        }
        .menu-filter-inner {
            max-width: 600px;
            margin: 0 auto;
        }
        .menu-filter-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .menu-filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .menu-filter-chip {
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 999px;
            border: 1px solid var(--prod-border);
            background: var(--surface);
            color: var(--text-muted);
            cursor: pointer;
            font-family: var(--font-body);
            transition: background .2s, border-color .2s, color .2s;
        }
        .menu-filter-chip:hover {
            border-color: var(--gold-border-soft);
            color: var(--gold);
        }
        .menu-filter-chip.is-active {
            background: var(--gold);
            color: var(--nav-active-fg);
            border-color: var(--gold);
        }
        .filter-hidden { display: none !important; }
        .menu-main--hidden { display: none !important; }
        .menu-flat-list { padding: 8px 16px 40px; max-width: 600px; margin: 0 auto; }
        .menu-flat-list > .cat-title { text-align: left; margin-top: 8px; }
        .menu-flat-cat-title { margin-top: 28px; scroll-margin-top: 100px; }
        .menu-flat-cat-title:first-child { margin-top: 0; }
        .menu-flat-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
        .menu-flat-badge {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .menu-flat-badge--offer { background: var(--red); color: #fff; }
        .menu-flat-badge--featured {
            background: linear-gradient(135deg, #f59e0b, #b45309);
            color: #fff;
        }
        .menu-flat-badge--rec {
            background: rgba(236, 72, 153, 0.22);
            color: #f472b6;
            border: 1px solid rgba(244, 114, 182, 0.35);
        }

        /* OFERTAS */
        .offers { padding: 28px 16px 12px; }
        .offers-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--red); color: #fff; font-size: 11px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            padding: 6px 14px; border-radius: 20px; margin-bottom: 16px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.4); }
            50%       { box-shadow: 0 0 0 8px rgba(220,38,38,0); }
        }
        .offers-scroll {
            display: flex; gap: 14px; overflow-x: auto;
            scroll-snap-type: x mandatory; scrollbar-width: none; padding-bottom: 8px;
        }
        .offers-scroll::-webkit-scrollbar { display: none; }
        .offer-card {
            flex-shrink: 0; width: 260px; scroll-snap-align: start;
            background: var(--surface); border-radius: var(--radius);
            overflow: hidden; border: 1px solid rgba(220,38,38,0.2);
            transition: transform .3s; position: relative; cursor: pointer;
        }
        .offer-card:active { transform: scale(0.98); }
        .offer-card img { width: 100%; height: 150px; object-fit: cover; display: block; }
        .offer-card-tag {
            position: absolute; top: 10px; right: 10px;
            background: var(--red); color: #fff; font-size: 11px; font-weight: 700;
            padding: 4px 10px; border-radius: 6px;
        }
        .offer-card-body { padding: 12px 14px; }
        .offer-card-name { font-family: var(--font-title); font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .offer-card-old { font-size: 13px; color: var(--text-muted); text-decoration: line-through; }
        .offer-card-new { font-size: 19px; font-weight: 700; color: var(--red); margin-left: 8px; }
        .offer-add-inline {
            position: absolute; right: 10px; bottom: 10px;
            padding: 7px 10px;
            border-radius: 999px;
            border: 1px solid var(--prod-border);
            background: var(--surface-el);
            color: var(--gold);
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .1s;
            white-space: nowrap;
        }
        .offer-add-inline:hover { border-color: var(--gold-border-hover); background: var(--surface); }
        .offer-add-inline:active { transform: translateY(1px); }
        .offer-add-inline:focus { outline: none; box-shadow: 0 0 0 3px var(--gold-focus-ring); }

        /* CATEGORÍA */
        .cat-section { padding: 28px 16px 12px; max-width: 600px; margin: 0 auto; }
        .cat-title {
            font-family: var(--font-title); font-size: 22px;
            color: var(--gold); margin-bottom: 4px; scroll-margin-top: 54px;
        }
        .cat-line { width: 36px; height: 2px; background: var(--gold-dim); margin-bottom: 18px; }

        /* PRODUCTO */
        .prod {
            display: flex; gap: 12px; background: var(--surface);
            border-radius: var(--radius); overflow: hidden; margin-bottom: 12px;
            border: 1px solid var(--prod-border); cursor: pointer;
            transition: transform .2s;
            max-width: 100%;
            box-sizing: border-box;
        }
        .prod:active { transform: scale(0.985); }
        .prod.has-offer { border-color: rgba(220,38,38,0.2); }
        .prod--featured {
            border-color: rgba(201, 168, 76, 0.35);
            box-shadow: 0 0 0 1px rgba(201, 168, 76, 0.12);
        }
        .prod-img { flex-shrink: 0; width: 110px; min-width: 88px; min-height: 110px; position: relative; }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .prod-img-badges {
            position: absolute; top: 6px; left: 6px; right: 6px;
            display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
            pointer-events: none; z-index: 1;
        }
        .prod-img-tag {
            background: var(--red); color: #fff; font-size: 9px; font-weight: 700;
            letter-spacing: 0.5px; text-transform: uppercase;
            padding: 3px 7px; border-radius: 4px;
        }

        /* Destacado / Recomendado: icono + jerarquía + microcopy + pulso suave */
        @keyframes commercial-pulse-gold {
            0%, 100% {
                box-shadow:
                    0 3px 14px rgba(201, 168, 76, 0.42),
                    0 0 0 1px rgba(138, 109, 43, 0.22),
                    inset 0 1px 0 rgba(255, 255, 255, 0.55);
            }
            50% {
                box-shadow:
                    0 5px 22px rgba(212, 184, 99, 0.52),
                    0 0 0 1px rgba(184, 146, 58, 0.32),
                    inset 0 1px 0 rgba(255, 255, 255, 0.65);
            }
        }
        @keyframes commercial-pulse-green {
            0%, 100% {
                box-shadow:
                    0 3px 14px rgba(20, 48, 40, 0.4),
                    inset 0 1px 0 rgba(255, 255, 255, 0.12);
            }
            50% {
                box-shadow:
                    0 5px 22px rgba(42, 92, 74, 0.48),
                    inset 0 1px 0 rgba(255, 255, 255, 0.18);
            }
        }
        .commercial-pill-row {
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
            margin-bottom: 10px;
        }

        /* Alérgenos en ficha compacta (carta sin abrir el producto) */
        .prod-footer-actions {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .prod-footer-actions .prod-allergens {
            margin-top: 0;
            flex: 1;
            min-width: 0;
        }
        .prod-footer-actions .prod-add-inline {
            flex-shrink: 0;
            margin-left: auto;
        }
        .prod-allergens {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin-top: 0;
            margin-bottom: 0;
        }
        /* Compactos en ficha (~−20 % respecto al thumb del modal para no competir con precio/botón) */
        .prod-allergen-icon {
            display: inline-flex;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            box-sizing: border-box;
            border: 1px solid var(--gold-border-soft);
            background: linear-gradient(165deg, var(--surface) 0%, var(--surface-el) 100%);
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.65);
            padding: 5px;
        }
        .prod-allergen-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
            filter: contrast(1.08) saturate(1.03);
        }
        .prod-allergen-chip {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.03em;
            padding: 5px 8px;
            border-radius: 7px;
            background: var(--chip-bg);
            border: 1px solid var(--gold-border-soft);
            color: var(--text-muted);
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.2;
        }
        .offer-card-body .prod-allergens {
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .commercial-pill {
            display: inline-flex; align-items: center; gap: 10px;
            font-family: var(--font-body);
            padding: 9px 14px 9px 10px;
            border-radius: 14px;
            line-height: 1.15;
            max-width: 100%;
        }
        .commercial-pill__visual {
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            width: 32px; height: 32px;
            border-radius: 50%;
        }
        .commercial-pill__svg { width: 17px; height: 17px; }
        .commercial-pill__text {
            display: flex; flex-direction: column; gap: 1px;
            min-width: 0;
        }
        .commercial-pill__title {
            font-size: 9px; font-weight: 800;
            letter-spacing: 0.12em; text-transform: uppercase;
        }
        .commercial-pill__hook {
            font-size: 10px; font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }
        .commercial-pill--featured {
            background: linear-gradient(155deg, #f8efd0 0%, #e6d48a 28%, #c9a84c 58%, #9a7828 100%);
            color: #1a1406;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow:
                0 3px 14px rgba(201, 168, 76, 0.42),
                0 0 0 1px rgba(138, 109, 43, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.55);
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.35);
            animation: commercial-pulse-gold 3s ease-in-out infinite;
        }
        .commercial-pill--featured .commercial-pill__visual {
            background: rgba(255, 255, 255, 0.45);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }
        .commercial-pill--featured .commercial-pill__hook { color: #3d2f0c; opacity: 0.88; }
        .commercial-pill--recommended {
            background: linear-gradient(155deg, #0f241e 0%, #1a3d32 40%, #256b52 100%);
            color: #ecfdf5;
            border: 1px solid rgba(201, 168, 76, 0.38);
            box-shadow:
                0 3px 14px rgba(20, 48, 40, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
            animation: commercial-pulse-green 3.2s ease-in-out infinite;
        }
        .commercial-pill--recommended .commercial-pill__visual { background: rgba(255, 255, 255, 0.12); }
        .commercial-pill--recommended .commercial-pill__hook { color: #d1fae5; opacity: 0.92; }
        .commercial-pill--modal {
            padding: 11px 16px 11px 12px;
            border-radius: 16px;
        }
        .commercial-pill--modal .commercial-pill__title { font-size: 10px; letter-spacing: 0.14em; }
        .commercial-pill--modal .commercial-pill__hook { font-size: 11px; }
        .commercial-pill--modal .commercial-pill__visual { width: 36px; height: 36px; }
        .commercial-pill--modal .commercial-pill__svg { width: 19px; height: 19px; }
        @media (prefers-reduced-motion: reduce) {
            .commercial-pill--featured,
            .commercial-pill--recommended { animation: none; }
        }

        .modal-commercial-chips {
            display: flex; flex-wrap: wrap; align-items: flex-start; gap: 12px; margin-bottom: 14px;
        }
        .prod-body {
            flex: 1; min-width: 0; padding: 12px 10px 12px 0;
            display: flex; flex-direction: column; justify-content: center;
            position: relative;
        }
        .prod-name {
            font-family: var(--font-title); font-size: 21px; font-weight: 700; line-height: 1.25; margin-bottom: 5px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .prod-desc {
            font-size: 12px; color: var(--text-muted); line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; margin-bottom: 8px;
        }
        .prod-add-inline {
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--gold-border-soft);
            background: var(--surface-el);
            color: var(--gold);
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .1s;
            white-space: nowrap;
        }
        .prod-add-inline:hover { border-color: var(--gold-border-hover); background: var(--surface); }
        .prod-add-inline:active { transform: translateY(1px); }
        .prod-add-inline:focus { outline: none; box-shadow: 0 0 0 3px var(--gold-focus-ring); }
        @media (max-width: 480px) {
            .prod-img { width: 92px; min-height: 96px; }
            .prod { gap: 10px; }
            .prod-body { padding-right: 6px; padding-bottom: 8px; }
            .prod-footer-actions { align-items: center; gap: 12px; }
            .prod-footer-actions .prod-add-inline { margin-left: auto; }
            /*
             * En móvil el icono no debe quedar más pequeño que el botón: antes 44px + padding
             * dejaba el pictograma casi ilegible con object-fit:contain en PNGs con mucho aire.
             */
            .prod-allergen-icon {
                width: 43px;
                height: 43px;
                min-width: 43px;
                min-height: 43px;
                border-radius: 10px;
                padding: 2px;
                border-width: 2px;
                border-color: var(--gold-border-hover);
            }
            .prod-allergen-icon img {
                transform: scale(1.05);
                transform-origin: center center;
                filter: contrast(1.12) saturate(1.08);
            }
            .prod-allergen-chip {
                font-size: 10px;
                padding: 6px 9px;
            }
        }
        .prod-price { font-size: 16px; font-weight: 700; color: var(--gold); }
        .prod-price-old { font-size: 13px; color: var(--text-muted); text-decoration: line-through; margin-right: 6px; }
        .prod-price-offer { color: var(--red); }

        /* MODAL — ficha centrada; fondo suave con blur ligero */
        .modal-bg {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: max(16px, env(safe-area-inset-top)) 16px max(16px, env(safe-area-inset-bottom));
            background: var(--modal-scrim);
            backdrop-filter: saturate(1.08) blur(6px);
            -webkit-backdrop-filter: saturate(1.08) blur(6px);
        }
        .modal-bg.open { display: flex; }
        .modal {
            position: relative;
            background: var(--surface);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            border-radius: 20px;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: var(--modal-shadow);
            animation: modalIn .3s ease-out;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.97) translateY(6px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
            border-radius: 20px 20px 0 0;
        }
        /* Texto del detalle centrado */
        .modal-body {
            padding: 20px 20px 32px;
            text-align: center;
            max-width: 100%;
        }
        .modal-name {
            font-family: var(--font-title); font-size: 22px; font-weight: 700; margin-bottom: 8px;
            text-align: center;
        }
        .modal-desc {
            font-size: 14px; line-height: 1.6; color: var(--text-muted); margin-bottom: 14px;
            text-align: center;
        }
        .modal-price-row {
            margin-top: 16px;
            margin-bottom: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--divider);
            text-align: center;
        }
        .modal-label {
            font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold);
            margin-bottom: 6px; text-align: center;
        }
        .modal-info {
            font-size: 13px; color: var(--text-muted); line-height: 1.5;
            padding: 10px 14px; background: var(--chip-bg);
            border-radius: 8px;
            border-left: none;
            border-top: 3px solid var(--gold-dim);
            margin: 0 auto 12px;
            max-width: 420px;
            text-align: center;
        }
        .modal-info--pairing { background: rgba(201,168,76,0.06); border-top-color: var(--gold); }
        .modal-allergens {
            display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;
        }
        .modal-allergen-chip {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; color: var(--text-muted);
            padding: 6px 10px; background: var(--chip-bg);
            border-radius: 8px; border: 1px solid rgba(201,168,76,0.15);
        }
        .modal-allergen-chip img {
            width: 22px; height: 22px; object-fit: cover; border-radius: 4px;
        }
        /* Alérgenos en modal: pictogramas compactos (antes un solo ítem crecía al ancho completo) */
        .modal-allergen-icons {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: center;
            gap: 10px 14px;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .modal-allergen-item {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 5px;
            max-width: 92px;
        }
        .modal-allergen-thumb {
            /* ~1/4 del tamaño que había con 1 alérgeno a ancho completo */
            width: 52px;
            height: 52px;
            max-width: 52px;
            max-height: 52px;
            box-sizing: border-box;
            margin: 0 auto;
            background: linear-gradient(165deg, #ffffff 0%, #f2ebe0 100%);
            border: 1px solid rgba(201, 168, 76, 0.45);
            border-radius: 10px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.14),
                inset 0 1px 0 rgba(255, 255, 255, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
        }
        .modal-allergen-thumb img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
            filter: contrast(1.08) saturate(1.03);
        }
        .modal-allergen-name {
            font-family: var(--font-body);
            font-size: 9px;
            font-weight: 700;
            line-height: 1.25;
            color: var(--gold-light);
            letter-spacing: 0.02em;
            max-width: 100%;
            padding: 0 2px;
            text-wrap: balance;
            hyphens: auto;
            -webkit-hyphens: auto;
        }
        .modal-allergen-tags {
            font-size: 13px; color: var(--text-muted); line-height: 1.45;
            margin: 0 auto 12px; padding: 8px 12px;
            background: var(--chip-bg); border-radius: 8px;
            max-width: 420px;
            text-align: center;
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            border: none;
            font-size: 20px;
            cursor: pointer;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
        }

        /* AVISO MODAL — solo fundido del velo (opacity); sin animación de la tarjeta. */
        .advice-overlay {
            display: flex;
            position: fixed;
            inset: 0;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(18, 19, 22, 0.62);
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.28s ease;
        }
        .advice-overlay.open {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }
        @media (prefers-reduced-motion: reduce) {
            .advice-overlay {
                transition: none;
            }
        }
        .advice-box {
            position: relative;
            width: min(92vw, 560px);
            transform-origin: left bottom;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--gold) 16%, transparent), transparent 26%),
                color-mix(in srgb, var(--surface) 10%, #fff 90%);
            border-radius: 28px;
            padding: 28px 28px 24px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
            border: 1px solid color-mix(in srgb, var(--gold) 30%, transparent);
            overflow: hidden;
        }
        .advice-box::before {
            content: "";
            display: block;
            width: 76px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--gold), color-mix(in srgb, var(--gold) 28%, transparent));
            margin-bottom: 18px;
        }
        .advice-close {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid rgba(17, 17, 17, 0.08);
            background: rgba(255,255,255,0.75);
            color: #2b2b2b;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .advice-close:hover { background: rgba(255,255,255,0.95); }
        .advice-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--gold) 16%, transparent);
            color: var(--gold-dim);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .advice-item + .advice-item {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--divider);
        }
        .advice-title {
            font-family: var(--font-title);
            font-size: clamp(32px, 5vw, 42px);
            line-height: 1.02;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 14px;
            padding-right: 44px;
        }
        .advice-text {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.72;
            margin-bottom: 0;
        }
        .advice-btn {
            display: block;
            width: 100%;
            margin-top: 24px;
            padding: 17px 22px;
            background: linear-gradient(180deg, var(--gold), var(--gold-dim));
            color: #fff;
            border: 1px solid color-mix(in srgb, var(--gold-dim) 65%, transparent);
            border-radius: 20px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.14),
                0 10px 24px color-mix(in srgb, var(--gold-dim) 30%, transparent);
        }
        .advice-btn:hover {
            background: linear-gradient(180deg, var(--gold-light), var(--gold));
        }
        @media (max-width: 520px) {
            .advice-box {
                width: min(94vw, 560px);
                border-radius: 24px;
                padding: 24px 20px 20px;
            }
            .advice-title {
                font-size: 28px;
                padding-right: 36px;
            }
            .advice-text {
                font-size: 15px;
                line-height: 1.62;
            }
            .advice-btn {
                margin-top: 20px;
                padding: 16px 18px;
                font-size: 17px;
            }
        }

        /* SCROLL TOP */
        .scroll-top {
            position: fixed; bottom: 20px; right: 20px; width: 44px; height: 44px;
            border-radius: 50%; background: var(--gold); color: var(--scroll-top-fg);
            border: none; cursor: pointer; display: none; align-items: center;
            justify-content: center; box-shadow: 0 4px 24px rgba(0,0,0,0.4); z-index: 50;
        }
        .scroll-top.show { display: flex; }

        /* FOOTER */
        footer { text-align: center; padding: 32px 16px max(28px, env(safe-area-inset-bottom)); font-size: 11px; color: var(--text-muted); }
        footer a { color: var(--gold-dim); text-decoration: none; }
        .footer-settings-wrap {
            margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--divider);
        }
        .footer-settings-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 10px 20px; border-radius: 999px;
            border: 1px solid var(--gold-dim); background: var(--surface-el);
            color: var(--gold); font-family: var(--font-body); font-size: 12px;
            font-weight: 600; letter-spacing: 0.4px; cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .footer-settings-btn:hover { border-color: var(--gold); background: var(--surface); }
        .footer-settings-btn svg { flex-shrink: 0; opacity: 0.95; }

        /* AJUSTES — apariencia */
        .settings-overlay {
            display: none; position: fixed; inset: 0; z-index: 2500;
            background: var(--modal-scrim); backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            align-items: flex-end; justify-content: center;
            padding: 0 0 env(safe-area-inset-bottom);
        }
        .settings-overlay.open { display: flex; }
        @media (min-width: 480px) {
            .settings-overlay { align-items: center; padding: 16px; }
        }
        .settings-panel {
            width: 100%; max-width: 400px;
            background: var(--surface); color: var(--text);
            border-radius: 22px 22px 0 0;
            box-shadow: var(--modal-shadow);
            padding: 24px 20px 28px;
            animation: settingsSheetIn .28s ease-out;
        }
        @media (min-width: 480px) {
            .settings-panel { border-radius: 20px; }
        }
        @keyframes settingsSheetIn {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .settings-panel h2 {
            font-family: var(--font-title); font-size: 21px; color: var(--gold);
            margin-bottom: 6px; text-align: center;
        }
        .settings-panel .settings-lead {
            font-size: 12px; color: var(--text-muted); text-align: center; margin-bottom: 20px; line-height: 1.45;
        }
        .theme-grid { display: flex; flex-direction: column; gap: 10px; }
        .theme-opt {
            display: flex; align-items: center; justify-content: center;
            width: 100%; padding: 14px 16px; border-radius: var(--radius);
            border: 2px solid var(--prod-border); background: var(--surface-el);
            color: var(--text); font-family: var(--font-body); font-size: 14px;
            font-weight: 600; cursor: pointer; transition: border-color .2s, background .2s, color .2s;
        }
        .theme-opt:hover { border-color: var(--gold-dim); }
        .theme-opt[aria-pressed="true"] {
            border-color: var(--gold);
            background: rgba(201, 168, 76, 0.14);
            color: var(--gold-light);
        }
        html[data-theme="light"] .theme-opt[aria-pressed="true"] {
            background: rgba(154, 115, 40, 0.14);
            color: var(--gold);
        }
        @media (prefers-color-scheme: light) {
            html[data-theme="system"] .theme-opt[aria-pressed="true"] {
                background: rgba(154, 115, 40, 0.14);
                color: var(--gold);
            }
        }
        .settings-close {
            margin-top: 20px; width: 100%; padding: 12px;
            border-radius: var(--radius); border: 1px solid var(--prod-border);
            background: transparent; color: var(--text-muted);
            font-family: var(--font-body); font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .settings-close:hover { color: var(--text); border-color: var(--gold-dim); }

        html[data-theme="dark"] { color-scheme: dark; }
        html[data-theme="light"] { color-scheme: light; }
        html[data-theme="system"] { color-scheme: light dark; }

        @media (min-width: 600px) {
            .cat-section, .offers { max-width: 600px; margin-left: auto; margin-right: auto; }
        }

        /* Pedido / carrito */
        .cart-fab {
            position: fixed; bottom: 20px; left: 20px; z-index: 55;
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 999px; border: none; cursor: pointer;
            font-family: var(--font-body); font-size: 13px; font-weight: 700;
            background: var(--surface-el); color: var(--gold);
            border: 1px solid var(--gold-dim);
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
            transition: background .2s, border-color .2s, transform .15s;
        }
        .cart-fab:hover { border-color: var(--gold); background: var(--surface); }
        .cart-fab.has-items {
            background: var(--gold); color: var(--nav-active-fg); border-color: var(--gold);
        }
        .cart-fab .cart-fab-count {
            min-width: 1.25rem; text-align: center; font-variant-numeric: tabular-nums;
        }

        .cart-overlay {
            display: none; position: fixed; inset: 0; z-index: 2400;
            background: var(--modal-scrim); backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            align-items: flex-end; justify-content: center;
            padding: 0 0 env(safe-area-inset-bottom);
        }
        .cart-overlay.open { display: flex; }
        @media (min-width: 480px) {
            .cart-overlay { align-items: center; padding: 16px; }
        }
        .cart-panel {
            width: 100%; max-width: 420px; max-height: 90vh; overflow-y: auto;
            background: var(--surface); color: var(--text);
            border-radius: 22px 22px 0 0;
            box-shadow: var(--modal-shadow);
            padding: 20px 18px 24px;
        }
        @media (min-width: 480px) {
            .cart-panel { border-radius: 20px; }
        }
        .cart-panel h2 {
            font-family: var(--font-title); font-size: 20px; color: var(--gold);
            margin-bottom: 14px; text-align: center;
        }
        .cart-line {
            display: flex; align-items: center; gap: 10px; padding: 10px 0;
            border-bottom: 1px solid var(--divider);
            font-size: 14px;
        }
        .cart-line-name { flex: 1; min-width: 0; font-weight: 600; }
        .cart-line-meta { font-size: 12px; color: var(--text-muted); }
        .cart-qty {
            display: inline-flex; align-items: center; gap: 6px;
        }
        .cart-qty button {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--prod-border);
            background: var(--surface-el); color: var(--text); font-size: 18px; line-height: 1;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .cart-qty span { min-width: 1.5rem; text-align: center; font-weight: 600; }
        .cart-empty { text-align: center; color: var(--text-muted); font-size: 14px; padding: 20px 8px; }
        .cart-form label { display: block; font-size: 11px; font-weight: 600; color: var(--text-muted); margin-top: 12px; margin-bottom: 4px; }
        .cart-form input, .cart-form textarea {
            width: 100%; padding: 10px 12px; border-radius: 10px;
            border: 2px solid var(--prod-border); background: var(--surface-el);
            color: var(--text); font-family: var(--font-body); font-size: 14px;
        }
        .cart-form textarea { min-height: 72px; resize: vertical; }
        .cart-submit {
            margin-top: 18px; width: 100%; padding: 14px;
            border: none; border-radius: var(--radius); cursor: pointer;
            font-family: var(--font-body); font-size: 15px; font-weight: 700;
            background: var(--gold); color: #fff;
        }
        .cart-submit:disabled { opacity: 0.45; cursor: not-allowed; }
        .cart-close-sheet {
            margin-top: 12px; width: 100%; padding: 10px;
            border-radius: var(--radius); border: 1px solid var(--prod-border);
            background: transparent; color: var(--text-muted);
            font-family: var(--font-body); font-size: 13px; font-weight: 600; cursor: pointer;
        }

        .modal-order-row { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--divider); }
        .modal-add-order-btn {
            width: 100%; padding: 12px 14px; border-radius: 10px; border: none; cursor: pointer;
            font-family: var(--font-body); font-size: 14px; font-weight: 700;
            background: var(--gold-dim); color: #fff;
        }
        .modal-add-order-btn:hover { filter: brightness(1.08); }
    </style>
    @if(!empty($menuBrandPalette['vars_dark']) && !empty($menuBrandPalette['vars_light']))
    <style id="menu-brand-from-logo">
        :root {
            @foreach($menuBrandPalette['vars_dark'] as $cssVar => $cssVal)
            {{ $cssVar }}: {{ $cssVal }};
            @endforeach
        }
        html[data-theme="light"] {
            @foreach($menuBrandPalette['vars_light'] as $cssVar => $cssVal)
            {{ $cssVar }}: {{ $cssVal }};
            @endforeach
        }
        @media (prefers-color-scheme: light) {
            html[data-theme="system"] {
                @foreach($menuBrandPalette['vars_light'] as $cssVar => $cssVal)
                {{ $cssVar }}: {{ $cssVal }};
                @endforeach
            }
        }
        @media (prefers-color-scheme: dark) {
            html[data-theme="system"] {
                @foreach($menuBrandPalette['vars_dark'] as $cssVar => $cssVal)
                {{ $cssVar }}: {{ $cssVal }};
                @endforeach
            }
        }
    </style>
    @endif
</head>
<body>

    {{-- AVISOS --}}
    @if($advices->count() > 0)
    <div class="advice-overlay" id="adviceOverlay" data-advice-open-on-load="{{ $showAlerts ? '1' : '0' }}">
        <div class="advice-box">
            <button class="advice-close" type="button" aria-label="{{ __('public_menu.close_advice') }}"
                    onclick="closeAdviceOverlay()">×</button>
            <div class="advice-badge">Aviso</div>
            @foreach($advices as $advice)
                <div class="advice-item">
                    <h3 class="advice-title">{{ $advice->translate($locale ?? 'es', 'title') }}</h3>
                    <p class="advice-text">{{ $advice->translate($locale ?? 'es', 'advice') }}</p>
                </div>
            @endforeach
            <button class="advice-btn" type="button" onclick="closeAdviceOverlay()">
                {{ __('public_menu.close_advice') }}
            </button>
        </div>
    </div>
    <script>
    (function () {
        function openAdviceOverlay() {
            var el = document.getElementById('adviceOverlay');
            if (!el) return;
            el.classList.add('open');
        }
        function closeAdviceOverlay() {
            var el = document.getElementById('adviceOverlay');
            if (!el) return;
            el.classList.remove('open');
        }
        window.openAdviceOverlay = openAdviceOverlay;
        window.closeAdviceOverlay = closeAdviceOverlay;
        function openAdviceOnLoad() {
            var el = document.getElementById('adviceOverlay');
            if (!el || el.getAttribute('data-advice-open-on-load') !== '1') return;
            openAdviceOverlay();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', openAdviceOnLoad);
        } else {
            openAdviceOnLoad();
        }
    })();
    </script>
    @endif

    {{-- HERO --}}
    <header class="hero">
        @php($publicLogoPath = \App\Models\Setting::get('admin_logo_path', null, $restaurant->id ?? (app()->bound('restaurant') ? app('restaurant')->id : null)))
        <div class="hero-logo">
            @if($publicLogoPath)
                <img src="{{ asset('storage/'.$publicLogoPath) }}" alt="{{ ($restaurant->name ?? config('app.name')) }} logo">
            @else
                BJ
            @endif
        </div>
        <h1>{{ $restaurant->name ?? config('app.name') }}</h1>
        <p>{{ __('public_menu.tagline') }}</p>
        <div class="hero-line"></div>
        @if($advices->count() > 0)
            <button class="advice-toggle" type="button" onclick="openAdviceOverlay()">
                {{ __('public_menu.view_notices') }}
            </button>
        @endif
    </header>

    {{-- NAV STICKY --}}
    <nav class="nav">
        <div class="nav-inner">
            @if($offers->count() > 0)
                <a href="#ofertas" class="nav-tab nav-tab--offer">{{ __('public_menu.nav_offers') }}</a>
            @endif
            @foreach($categories as $cat)
                @if($cat->products->count() > 0)
                    <a href="#cat-{{ $cat->id }}" class="nav-tab">{{ $cat->translate($locale ?? 'es', 'name') }}</a>
                @endif
            @endforeach

            <button type="button" class="nav-tab nav-tab--settings js-open-theme-settings"
                    aria-label="{{ __('public_menu.settings_theme_aria') }}" aria-haspopup="dialog" aria-controls="themeSettingsOverlay">
                <span class="nav-tab-settings-inner">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>{{ __('public_menu.settings') }}</span>
                </span>
            </button>
        </div>
    </nav>

    <div class="menu-filter-bar">
        <div class="menu-filter-inner">
            <span class="menu-filter-label">{{ __('public_menu.filter_label') }}</span>
            <div class="menu-filter-chips" role="group" aria-label="{{ __('public_menu.filter_aria') }}">
                <button type="button" class="menu-filter-chip is-active" data-filter="all">{{ __('public_menu.filter_categories') }}</button>
                <button type="button" class="menu-filter-chip" data-filter="offer">{{ __('public_menu.filter_offers') }}</button>
                <button type="button" class="menu-filter-chip" data-filter="featured">{{ __('public_menu.filter_featured') }}</button>
                <button type="button" class="menu-filter-chip" data-filter="recommended">{{ __('public_menu.filter_recommended') }}</button>
                <button type="button" class="menu-filter-chip" data-filter="flat">{{ __('public_menu.filter_flat') }}</button>
            </div>
        </div>
    </div>

    <main>
        <div id="menuMainContent">
        {{-- OFERTAS DEL DÍA --}}
        @if($offers->count() > 0)
        <section class="offers" id="ofertas" style="scroll-margin-top:54px">
            <div class="offers-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
                {{ __('public_menu.offers_badge') }}
            </div>
            <div class="offers-scroll">
                @foreach($offers as $product)
                <div class="offer-card"
                     data-has-offer="1"
                     data-featured="{{ $product->featured ? '1' : '0' }}"
                     data-recommended="{{ $product->recommended ? '1' : '0' }}"
                     onclick="openModal({{ $product->id }})">
                    <img src="{{ $product->photo ? asset('storage/'.$product->photo) : asset('img/noimg.png') }}" alt="{{ $product->name }}" loading="lazy"
                         onerror="this.onerror=null;this.src={{ json_encode(asset('img/noimg.png')) }};">
                    <span class="offer-card-tag">{{ $product->offer_badge ?? __('public_menu.offer_default') }}</span>
                    <div class="offer-card-body">
                        @include('partials.menu-commercial-pills', ['featured' => $product->featured, 'recommended' => $product->recommended])
                        <div class="offer-card-name">{{ $product->translate($locale ?? 'es', 'name') }}</div>
                        <div>
                            <span class="offer-card-old">{{ $product->price }}</span>
                            <span class="offer-card-new">{{ $product->offer_price }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:5px;font-style:italic;">
                            {{ __('public_menu.offer_limited') }}
                        </div>
                    </div>

                    @if(!empty($ordersMode))
                        <button type="button" class="offer-add-inline"
                                onclick="event.stopPropagation();addToCartFromModal({{ $product->id }});"
                                aria-label="{{ __('public_menu.add_to_list_aria', ['name' => $product->translate($locale ?? 'es', 'name')]) }}">
                            {{ __('public_menu.add') }}
                        </button>
                    @endif
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- CATEGORÍAS CON PRODUCTOS --}}
        @foreach($categories as $cat)
            @if($cat->products->count() > 0)
            <section class="cat-section">
                <h2 class="cat-title" id="cat-{{ $cat->id }}">{{ $cat->translate($locale ?? 'es', 'name') }}</h2>
                <div class="cat-line"></div>

                @foreach($cat->products as $product)
                <div class="prod {{ $product->isOfferActive() ? 'has-offer' : '' }} {{ $product->featured ? 'prod--featured' : '' }}"
                     data-has-offer="{{ $product->isOfferActive() ? '1' : '0' }}"
                     data-featured="{{ $product->featured ? '1' : '0' }}"
                     data-recommended="{{ $product->recommended ? '1' : '0' }}"
                     onclick="openModal({{ $product->id }})">
                    <div class="prod-img">
                        <img src="{{ $product->photo ? asset('storage/'.$product->photo) : asset('img/noimg.png') }}"
                             alt="{{ $product->name }}" loading="lazy"
                             onerror="this.onerror=null;this.src={{ json_encode(asset('img/noimg.png')) }};">
                        @if($product->isOfferActive())
                        <div class="prod-img-badges">
                            <span class="prod-img-tag">{{ $product->offer_badge ?? __('public_menu.offer_default') }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="prod-body">
                        @include('partials.menu-commercial-pills', ['featured' => $product->featured, 'recommended' => $product->recommended])
                        <div class="prod-name">{{ $product->translate($locale ?? 'es', 'name') }}</div>
                        <div class="prod-desc">{{ $product->translate($locale ?? 'es', 'description') }}</div>
                        <div>
                            @if($product->isOfferActive())
                                <span class="prod-price-old">{{ $product->price }}</span>
                                <span class="prod-price prod-price-offer">{{ $product->offer_price }}</span>
                            @else
                                <span class="prod-price">{{ $product->price }}</span>
                            @endif
                        </div>
                        @if(!empty($ordersMode) || $product->visibleAllergens->isNotEmpty())
                        <div class="prod-footer-actions">
                            @include('partials.menu-product-allergen-icons', ['product' => $product, 'locale' => $locale ?? 'es'])
                            @if(!empty($ordersMode))
                            <button type="button" class="prod-add-inline"
                                    onclick="event.stopPropagation();addToCartFromModal({{ $product->id }});"
                                    aria-label="{{ __('public_menu.add_to_list_aria', ['name' => $product->translate($locale ?? 'es', 'name')]) }}">
                                {{ __('public_menu.add') }}
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </section>
            @endif
        @endforeach
        </div>

        <section id="menuFlatList" class="menu-flat-list" hidden>
            <h2 class="cat-title">{{ __('public_menu.all_dishes') }}</h2>
            <div class="cat-line"></div>
            <div id="menuFlatBody"></div>
        </section>
    </main>

    {{-- MODAL PRODUCTO --}}
    <div class="modal-bg" id="modalBg" onclick="if(event.target===this)closeModal()">
        <div class="modal" id="modalContent"></div>
    </div>

    {{-- SCROLL TOP --}}
    <button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    {{-- Botón lista/pedido — siempre visible; comportamiento según si pedidos están activos --}}
    @if(empty($ordersMode))
    <button type="button" class="cart-fab" id="cartFab" onclick="showOrdersComingSoon()">
        {{ __('public_menu.cart_fab_list') }}
    </button>
    {{-- Toast "próximamente" --}}
    <div id="ordersSoonToast" style="display:none;position:fixed;bottom:74px;left:20px;z-index:200;
         background:var(--surface);color:var(--text);border:1px solid var(--gold-dim);
         border-radius:14px;padding:12px 18px;font-size:13px;font-weight:600;
         box-shadow:0 4px 20px rgba(0,0,0,0.35);max-width:260px;line-height:1.4;">
        {{ __('public_menu.orders_soon') }}
        <button onclick="document.getElementById('ordersSoonToast').style.display='none'"
                style="display:block;margin-top:8px;font-size:12px;opacity:.6;background:none;border:none;cursor:pointer;color:inherit;padding:0;">
            {{ __('public_menu.close') }}
        </button>
    </div>
    @else
    <button type="button" class="cart-fab" id="cartFab" onclick="toggleCartDrawer(true)" aria-controls="cartOverlay" aria-haspopup="dialog">
        {{ __('public_menu.cart_fab_list') }} · <span class="cart-fab-count" id="cartCount">0</span>
    </button>

    <div class="cart-overlay" id="cartOverlay" role="dialog" aria-modal="true" aria-labelledby="cartTitle"
         onclick="if(event.target===this)toggleCartDrawer(false)">
        <div class="cart-panel" onclick="event.stopPropagation()">
            <h2 id="cartTitle">{{ ($ordersMode ?? 'list') === 'order' ? __('public_menu.cart_title_order') : __('public_menu.cart_title_list') }}</h2>
            <div id="cartLines"></div>
            @if(($ordersMode ?? 'list') === 'order')
                <form id="cartForm" class="cart-form" onsubmit="submitOrder(event)">
                    <label for="cartCustomerName">{{ __('public_menu.cart_name') }} <span style="font-weight:400;opacity:.75">{{ __('public_menu.cart_optional') }}</span></label>
                    <input id="cartCustomerName" name="customer_name" type="text" autocomplete="name" maxlength="120" placeholder="{{ __('public_menu.cart_name_placeholder') }}">
                    <label for="cartCustomerPhone">{{ __('public_menu.cart_phone') }} <span style="font-weight:400;opacity:.75">{{ __('public_menu.cart_recommended') }}</span></label>
                    <input id="cartCustomerPhone" name="customer_phone" type="tel" autocomplete="tel" maxlength="40" placeholder="{{ __('public_menu.cart_phone_placeholder') }}">
                    <label for="cartNotes">{{ __('public_menu.cart_notes') }} <span style="font-weight:400;opacity:.75">{{ __('public_menu.cart_optional') }}</span></label>
                    <textarea id="cartNotes" name="customer_notes" maxlength="2000" placeholder="{{ __('public_menu.cart_notes_placeholder') }}"></textarea>
                    <button type="submit" class="cart-submit" id="cartSubmit">{{ __('public_menu.cart_submit') }}</button>
                </form>
            @else
                <div class="cart-form" style="margin-top:14px">
                    <p class="cart-empty" style="padding:0;margin:0;text-align:left">
                        {{ __('public_menu.cart_empty_hint') }}
                    </p>
                    <button type="button" class="cart-submit" id="cartClear" onclick="clearCartList()" style="margin-top:14px">
                        {{ __('public_menu.cart_clear') }}
                    </button>
                </div>
            @endif
            <button type="button" class="cart-close-sheet" onclick="toggleCartDrawer(false)">{{ __('public_menu.close') }}</button>
        </div>
    </div>
    @endif

    <footer>
        <p>&copy; <script>document.write(new Date().getFullYear())</script> {{ __('public_menu.footer_line', ['name' => ($restaurant->name ?? config('app.name'))]) }}</p>
        <p style="margin-top:4px">{{ __('public_menu.footer_credit') }} <a href="https://cositt.com/" target="_blank">Cositt Technology&reg;</a></p>
        <div class="footer-settings-wrap">
            <button type="button" class="footer-settings-btn js-open-theme-settings"
                    aria-haspopup="dialog" aria-controls="themeSettingsOverlay">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                {{ __('public_menu.settings') }}
            </button>
        </div>
    </footer>

    <div class="settings-overlay" id="themeSettingsOverlay" role="dialog" aria-modal="true"
         aria-labelledby="themeSettingsTitle" aria-hidden="true"
         onclick="if(event.target===this)window.closeThemeSettings&&closeThemeSettings()">
        <div class="settings-panel" onclick="event.stopPropagation()">
            <h2 id="themeSettingsTitle">{{ __('public_menu.theme_title') }}</h2>
            <p class="settings-lead">{{ __('public_menu.theme_lead') }}</p>
            <div class="theme-grid" role="group" aria-label="{{ __('public_menu.theme_appearance_aria') }}">
                <button type="button" class="theme-opt" data-theme-value="light" aria-pressed="false">{{ __('public_menu.theme_light') }}</button>
                <button type="button" class="theme-opt" data-theme-value="dark" aria-pressed="false">{{ __('public_menu.theme_dark') }}</button>
                <button type="button" class="theme-opt" data-theme-value="system" aria-pressed="false">{{ __('public_menu.theme_system') }}</button>
            </div>
            <button type="button" class="settings-close" id="closeThemeSettings">{{ __('public_menu.close') }}</button>
        </div>
    </div>

    <script>
    window.BJ_MENU_I18N = @json($publicMenuStrings ?? []);
    </script>

    {{-- DATOS PARA JS --}}
    <script>
    (function () {
        var THEME_KEY = 'bjCartaTheme';

        function getStoredTheme() {
            try {
                var t = localStorage.getItem(THEME_KEY) || 'light';
                if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'light';
                return t;
            } catch (e) {
                return 'light';
            }
        }

        window.applyMenuTheme = function (mode) {
            if (mode !== 'light' && mode !== 'dark' && mode !== 'system') mode = 'light';
            document.documentElement.setAttribute('data-theme', mode);
            try {
                localStorage.setItem(THEME_KEY, mode);
            } catch (e) {}
            document.querySelectorAll('.theme-opt').forEach(function (btn) {
                var v = btn.getAttribute('data-theme-value');
                btn.setAttribute('aria-pressed', v === mode ? 'true' : 'false');
            });
        };

        window.openThemeSettings = function () {
            var el = document.getElementById('themeSettingsOverlay');
            if (!el) return;
            el.classList.add('open');
            el.setAttribute('aria-hidden', 'false');
            window.applyMenuTheme(getStoredTheme());
        };

        window.closeThemeSettings = function () {
            var el = document.getElementById('themeSettingsOverlay');
            if (!el) return;
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
        };

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-open-theme-settings').forEach(function (btn) {
                btn.addEventListener('click', function () { window.openThemeSettings(); });
            });
            var closeBtn = document.getElementById('closeThemeSettings');
            if (closeBtn) closeBtn.addEventListener('click', window.closeThemeSettings);
            document.querySelectorAll('.theme-opt').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    window.applyMenuTheme(btn.getAttribute('data-theme-value'));
                });
            });

            try {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                    if (getStoredTheme() === 'system') {
                        document.documentElement.setAttribute('data-theme', 'system');
                    }
                });
            } catch (e) {}

            window.addEventListener('storage', function (e) {
                if (e.key !== THEME_KEY || !e.newValue) return;
                if (e.newValue === 'light' || e.newValue === 'dark' || e.newValue === 'system') {
                    document.documentElement.setAttribute('data-theme', e.newValue);
                    document.querySelectorAll('.theme-opt').forEach(function (btn) {
                        var v = btn.getAttribute('data-theme-value');
                        btn.setAttribute('aria-pressed', v === e.newValue ? 'true' : 'false');
                    });
                }
            });
        });
    })();


    const PRODUCTS_DATA = @json($productsData);
    window.MENU_CATEGORY_ORDER = @json($categoryOrderIds ?? []);
    window.MENU_CATEGORIES_DATA = @json($categoriesData ?? []);
    window.BJ_PUBLIC_STORAGE = @json(rtrim(url('/storage'), '/'));
    window.BJ_NOIMG_URL = @json(asset('img/noimg.png'));
    window.ORDERS_MODE = @json($ordersMode ?? 'list');
    window.ORDERS_ENABLED = !!window.ORDERS_MODE;
    var CART_STORAGE_KEY = 'bjCartDraft';

    function getCartLines() {
        if (!window.ORDERS_ENABLED) return [];
        try {
            var raw = sessionStorage.getItem(CART_STORAGE_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function saveCartLines(lines) {
        try {
            sessionStorage.setItem(CART_STORAGE_KEY, JSON.stringify(lines));
        } catch (e) {}
        updateCartBadge();
        renderCartPanel();
    }

    function updateCartBadge() {
        var lines = getCartLines();
        var n = lines.reduce(function (s, l) { return s + (l.qty || 0); }, 0);
        var el = document.getElementById('cartCount');
        if (el) el.textContent = n;
        var fab = document.getElementById('cartFab');
        if (fab) fab.classList.toggle('has-items', n > 0);
        var clearBtn = document.getElementById('cartClear');
        if (clearBtn) clearBtn.disabled = n === 0;
        var submit = document.getElementById('cartSubmit');
        if (submit) submit.disabled = n === 0;
    }

    function renderCartPanel() {
        var wrap = document.getElementById('cartLines');
        if (!wrap) return;
        var t = window.BJ_MENU_I18N || {};
        var lines = getCartLines();
        if (!lines.length) {
            wrap.innerHTML = '<p class="cart-empty">' + escHtml(t.cart_empty_no_items || 'Aún no has añadido platos. Abre una ficha y pulsa «Añadir al pedido».') + '</p>';
            return;
        }
        var unitSuf = escHtml(t.cart_unit_price_suffix || 'u.');
        wrap.innerHTML = lines.map(function (l, idx) {
            return '<div class="cart-line" data-idx="' + idx + '">' +
                '<div style="flex:1;min-width:0">' +
                '<div class="cart-line-name">' + escHtml(l.name) + '</div>' +
                '<div class="cart-line-meta">' + escHtml(l.priceLabel) + ' ' + unitSuf + '</div></div>' +
                '<div class="cart-qty">' +
                '<button type="button" aria-label="' + escHtml(t.cart_qty_minus_aria || 'Quitar uno') + '" onclick="cartChangeQty(' + idx + ',-1)">−</button>' +
                '<span>' + l.qty + '</span>' +
                '<button type="button" aria-label="' + escHtml(t.cart_qty_plus_aria || 'Añadir uno') + '" onclick="cartChangeQty(' + idx + ',1)">+</button>' +
                '</div></div>';
        }).join('');
    }

    function cartChangeQty(idx, delta) {
        var lines = getCartLines();
        if (!lines[idx]) return;
        lines[idx].qty += delta;
        if (lines[idx].qty <= 0) lines.splice(idx, 1);
        saveCartLines(lines);
    }

    function addToCartFromModal(productId) {
        if (!window.ORDERS_ENABLED) return;
        var p = PRODUCTS_DATA[productId];
        if (!p) return;
        var lines = getCartLines();
        var i = lines.findIndex(function (l) { return l.product_id === productId; });
        var label = (p.offer && p.offer_price) ? p.offer_price : p.price;
        if (i >= 0) lines[i].qty += 1;
        else lines.push({ product_id: productId, name: p.name, priceLabel: String(label || ''), qty: 1 });
        saveCartLines(lines);
    }

    function showOrdersComingSoon() {
        var t = document.getElementById('ordersSoonToast');
        if (!t) return;
        t.style.display = 'block';
        clearTimeout(t._timer);
        t._timer = setTimeout(function(){ t.style.display = 'none'; }, 5000);
    }

    function toggleCartDrawer(open) {
        if (!window.ORDERS_ENABLED) return;
        var o = document.getElementById('cartOverlay');
        if (!o) return;
        if (open === true) o.classList.add('open');
        else if (open === false) o.classList.remove('open');
        else o.classList.toggle('open');
        var isOpen = o.classList.contains('open');
        o.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (isOpen) renderCartPanel();
    }

    function clearCartList() {
        if (!window.ORDERS_ENABLED) return;
        saveCartLines([]);
    }

    function submitOrder(ev) {
        if (window.ORDERS_MODE !== 'order') return;
        ev.preventDefault();
        var t = window.BJ_MENU_I18N || {};
        var lines = getCartLines();
        if (!lines.length) return;
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (!tokenMeta || !tokenMeta.content) {
            alert(t.alert_csrf_missing || 'Falta el token de seguridad. Recarga la página.');
            return;
        }
        var items = lines.map(function (l) {
            return { product_id: l.product_id, quantity: l.qty };
        });
        var form = document.getElementById('cartForm');
        var fd = new FormData(form);
        var payload = {
            items: items,
            customer_name: (fd.get('customer_name') || '').toString().trim() || null,
            customer_phone: (fd.get('customer_phone') || '').toString().trim() || null,
            customer_notes: (fd.get('customer_notes') || '').toString().trim() || null
        };
        var btn = document.getElementById('cartSubmit');
        if (btn) { btn.disabled = true; }

        fetch(@json(url('/api/orders')), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': tokenMeta.content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().then(function (j) { return { ok: r.ok, status: r.status, body: j }; });
        }).then(function (res) {
            if (btn) { btn.disabled = false; }
            if (res.ok && res.body && res.body.ok) {
                saveCartLines([]);
                toggleCartDrawer(false);
                var okMsg = (t.order_sent_success || 'Pedido #:order_id enviado. Gracias.').replace(':order_id', String(res.body.order_id));
                alert(okMsg);
                return;
            }
            var msg = (res.body && res.body.message) ? res.body.message : (t.order_send_failed_default || 'No se pudo enviar el pedido.');
            if (res.body && res.body.errors) {
                var k = Object.keys(res.body.errors)[0];
                if (k && res.body.errors[k] && res.body.errors[k][0]) msg = res.body.errors[k][0];
            }
            alert(msg);
        }).catch(function () {
            if (btn) { btn.disabled = false; }
            alert(t.alert_network_error || 'Error de red. Inténtalo de nuevo.');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMenuCommercialFilter();
        updateCartBadge();
        renderCartPanel();
    });

    function escHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function menuProductMatchesFilter(el, mode) {
        if (mode === 'offer') return el.getAttribute('data-has-offer') === '1';
        if (mode === 'featured') return el.getAttribute('data-featured') === '1';
        if (mode === 'recommended') return el.getAttribute('data-recommended') === '1';
        return true;
    }

    function renderMenuFlatList() {
        var body = document.getElementById('menuFlatBody');
        if (!body || typeof PRODUCTS_DATA === 'undefined') return;
        var order = window.MENU_CATEGORY_ORDER || [];
        var names = {};
        (window.MENU_CATEGORIES_DATA || []).forEach(function (c) {
            names[String(c.id)] = c.name;
        });
        var used = {};
        var html = '';
        order.forEach(function (cid) {
            var prods = Object.keys(PRODUCTS_DATA).map(function (k) { return PRODUCTS_DATA[k]; })
                .filter(function (p) { return String(p.category_id) === String(cid); })
                .sort(function (a, b) { return (a.sort_order || 0) - (b.sort_order || 0); });
            if (!prods.length) return;
            var title = names[String(cid)] || '';
            html += '<h3 class="cat-title menu-flat-cat-title">' + escHtml(title) + '</h3><div class="cat-line"></div>';
            prods.forEach(function (p) {
                used[String(p.id)] = true;
                html += menuFlatRowHtml(p);
            });
        });
        var orphans = Object.keys(PRODUCTS_DATA).map(function (k) { return PRODUCTS_DATA[k]; })
            .filter(function (p) { return !used[String(p.id)]; })
            .sort(function (a, b) { return (a.sort_order || 0) - (b.sort_order || 0); });
        if (orphans.length) {
            var t = window.BJ_MENU_I18N || {};
            html += '<h3 class="cat-title menu-flat-cat-title">' + escHtml(t.flat_other || 'Otros') + '</h3><div class="cat-line"></div>';
            orphans.forEach(function (p) {
                html += menuFlatRowHtml(p);
            });
        }
        body.innerHTML = html;
    }

    function menuFlatRowHtml(p) {
        var t = window.BJ_MENU_I18N || {};
        var img = p.photo ? (window.BJ_PUBLIC_STORAGE + '/' + p.photo) : window.BJ_NOIMG_URL;
        var desc = (p.description || '').toString();
        if (desc.length > 140) desc = desc.slice(0, 137) + '…';
        var priceBlock = (p.offer && p.offer_price)
            ? '<span class="prod-price-old">' + escHtml(String(p.price)) + '</span><span class="prod-price prod-price-offer">' + escHtml(String(p.offer_price)) + '</span>'
            : '<span class="prod-price">' + escHtml(String(p.price)) + '</span>';
        var badges = '';
        if (p.offer) badges += '<span class="menu-flat-badge menu-flat-badge--offer">' + escHtml(p.offer_badge || t.offer_default || 'Oferta') + '</span>';
        if (p.featured) badges += '<span class="menu-flat-badge menu-flat-badge--featured">' + escHtml(t.flat_badge_featured || 'Destacado') + '</span>';
        if (p.recommended) badges += '<span class="menu-flat-badge menu-flat-badge--rec">' + escHtml(t.flat_badge_recommended || 'Recomendado') + '</span>';
        var addBtn = window.ORDERS_ENABLED
            ? '<button type="button" class="prod-add-inline" onclick="event.stopPropagation();addToCartFromModal(' + p.id + ')">' + escHtml(t.add || 'Añadir') + '</button>'
            : '';
        var hasAl = p.allergens && p.allergens.length;
        var footer = (window.ORDERS_ENABLED || hasAl)
            ? ('<div class="prod-footer-actions">' + menuCardAllergensHtml(p) + addBtn + '</div>')
            : '';
        return '<div class="prod menu-flat-prod" onclick="openModal(' + p.id + ')">' +
            '<div class="prod-img"><img src="' + escHtml(img) + '" alt="" loading="lazy" onerror="this.onerror=null;this.src=' + JSON.stringify(window.BJ_NOIMG_URL) + '"></div>' +
            '<div class="prod-body"><div class="menu-flat-badges">' + badges + '</div>' +
            '<div class="prod-name">' + escHtml(p.name) + '</div>' +
            '<div class="prod-desc">' + escHtml(desc) + '</div><div>' + priceBlock + '</div>' +
            footer +
            '</div></div>';
    }

    function applyCommercialFilter(mode) {
        var main = document.getElementById('menuMainContent');
        var flat = document.getElementById('menuFlatList');
        if (!main || !flat) return;

        if (mode === 'flat') {
            // Vista en lista: mantener siempre visible el carrusel de ofertas y renderizar la lista debajo.
            main.classList.remove('menu-main--hidden');
            flat.hidden = false;
            // Ocultar secciones de categorías, pero no el carrusel de ofertas.
            document.querySelectorAll('.cat-section').forEach(function (sec) {
                sec.classList.add('filter-hidden');
            });
            var offSec = document.querySelector('.offers');
            if (offSec) offSec.classList.remove('filter-hidden');
            document.querySelectorAll('.offer-card').forEach(function (el) {
                el.classList.remove('filter-hidden');
            });
            renderMenuFlatList();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        main.classList.remove('menu-main--hidden');
        flat.hidden = true;

        // Reset filtros: el carrusel de ofertas siempre visible.
        document.querySelectorAll('.offer-card, .cat-section .prod').forEach(function (el) {
            el.classList.remove('filter-hidden');
        });
        document.querySelectorAll('.cat-section, .offers').forEach(function (sec) {
            sec.classList.remove('filter-hidden');
        });

        if (mode === 'all') return;

        // Aplicar filtro solo a productos de categorías (no a ofertas).
        document.querySelectorAll('.cat-section .prod').forEach(function (el) {
            var ok = menuProductMatchesFilter(el, mode);
            el.classList.toggle('filter-hidden', !ok);
        });

        document.querySelectorAll('.cat-section').forEach(function (sec) {
            var n = sec.querySelectorAll('.prod:not(.filter-hidden)').length;
            sec.classList.toggle('filter-hidden', n === 0);
        });
        // El carrusel de ofertas siempre se muestra (si existe en el DOM).
        var offSec = document.querySelector('.offers');
        if (offSec) offSec.classList.remove('filter-hidden');
        document.querySelectorAll('.offer-card').forEach(function (el) {
            el.classList.remove('filter-hidden');
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function initMenuCommercialFilter() {
        var chips = document.querySelectorAll('.menu-filter-chip');
        if (!chips.length) return;
        // Por defecto, siempre arrancar "Por categorías", pero manteniendo el carrusel de ofertas visible.
        // No respetamos el último filtro guardado.
        var saved = 'all';
        var allowed = { all: 1, offer: 1, featured: 1, recommended: 1, flat: 1 };
        if (!allowed[saved]) saved = 'all';
        chips.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var mode = btn.getAttribute('data-filter');
                chips.forEach(function (c) {
                    c.classList.toggle('is-active', c.getAttribute('data-filter') === mode);
                });
                try { localStorage.setItem('bjMenuCommercialFilter', mode); } catch (e) {}
                applyCommercialFilter(mode);
            });
            btn.classList.toggle('is-active', btn.getAttribute('data-filter') === saved);
        });
        applyCommercialFilter(saved);
    }

    function allergenImgSrc(a) {
        if (a.image_url) return a.image_url;
        if (a.image) return window.BJ_PUBLIC_STORAGE + '/' + a.image;
        return '';
    }

    function menuCardAllergensHtml(p) {
        if (!p || !p.allergens || !p.allergens.length) return '';
        var t = window.BJ_MENU_I18N || {};
        var parts = [];
        p.allergens.forEach(function (a) {
            var src = allergenImgSrc(a);
            var name = (a.name || '').toString();
            var title = escHtml(name);
            if (src) {
                parts.push(
                    '<span class="prod-allergen-icon" title="' + title + '">' +
                    '<img src="' + escHtml(src) + '" alt="' + title + '" loading="lazy" width="22" height="22" ' +
                    'onerror="this.onerror=null;this.src=window.BJ_NOIMG_URL">' +
                    '</span>'
                );
            } else {
                var abbr = name.length > 12 ? name.slice(0, 10) + '…' : name;
                parts.push('<span class="prod-allergen-chip" title="' + title + '">' + escHtml(abbr) + '</span>');
            }
        });
        return '<div class="prod-allergens" role="group" aria-label="' + escHtml(t.allergens_aria_group || 'Alérgenos') + '">' + parts.join('') + '</div>';
    }

    function commercialPillModalFeaturedHtml() {
        var t = window.BJ_MENU_I18N || {};
        return '<span class="commercial-pill commercial-pill--featured commercial-pill--modal"><span class="commercial-pill__visual" aria-hidden="true"><svg class="commercial-pill__svg" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm2.7-2h8.6l.5-5.1-2.4 2.2-2.5-4.4-2.5 4.4-2.4-2.2L7.2 14z"/></svg></span><span class="commercial-pill__text"><span class="commercial-pill__title">' + escHtml(t.pill_featured || 'Destacado') + '</span><span class="commercial-pill__hook">' + escHtml(t.pill_hook_featured || '') + '</span></span></span>';
    }
    function commercialPillModalRecommendedHtml() {
        var t = window.BJ_MENU_I18N || {};
        return '<span class="commercial-pill commercial-pill--recommended commercial-pill--modal"><span class="commercial-pill__visual" aria-hidden="true"><svg class="commercial-pill__svg" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></span><span class="commercial-pill__text"><span class="commercial-pill__title">' + escHtml(t.pill_recommended || 'Recomendado') + '</span><span class="commercial-pill__hook">' + escHtml(t.pill_hook_recommended || '') + '</span></span></span>';
    }

    function openModal(id) {
        const p = PRODUCTS_DATA[id];
        if (!p) return;
        var t = window.BJ_MENU_I18N || {};

        const img = p.photo ? window.BJ_PUBLIC_STORAGE + '/' + p.photo : window.BJ_NOIMG_URL;

        let priceHtml = p.offer
            ? `<span style="font-size:16px;color:var(--text-muted);text-decoration:line-through">${p.price}</span>
               <span style="font-size:24px;font-weight:700;color:var(--red);margin-left:8px">${p.offer_price}</span>
               <span style="display:inline-block;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;margin-left:8px">${escHtml(p.offer_badge || t.offer_default || 'Oferta')}</span>`
            : `<span style="font-size:24px;font-weight:700;color:var(--gold)">${p.price}</span>`;

        const hasAllergens = p.allergens && p.allergens.length;
        const allerText = (p.aller && String(p.aller).trim()) ? String(p.aller).trim() : '';

        let allergensTextBlock = '';
        let allergensIconsBlock = '';

        const withImg = hasAllergens
            ? p.allergens.filter(function(a) { return !!allergenImgSrc(a); })
            : [];
        const noImg = hasAllergens
            ? p.allergens.filter(function(a) { return !allergenImgSrc(a); })
            : [];

        const hasAllergenTextSection = !!(allerText || noImg.length);

        if (hasAllergenTextSection) {
            allergensTextBlock += '<p class="modal-label">' + escHtml(t.modal_allergens || 'Alérgenos') + '</p>';
            if (allerText) {
                allergensTextBlock += '<div class="modal-info">' + escHtml(allerText) + '</div>';
            }
            if (noImg.length) {
                allergensTextBlock += '<p class="modal-allergen-tags">' + noImg.map(function(a) { return escHtml(a.name); }).join(' · ') + '</p>';
            }
        }

        if (withImg.length) {
            allergensIconsBlock += '<div class="modal-allergen-icons">';
            withImg.forEach(function(a) {
                allergensIconsBlock += '<div class="modal-allergen-item">';
                allergensIconsBlock += '<div class="modal-allergen-thumb" title="' + escHtml(a.name) + '">';
                allergensIconsBlock += '<img src="' + escHtml(allergenImgSrc(a)) + '" alt="' + escHtml(a.name) + '" loading="lazy" onerror="this.onerror=null;this.src=window.BJ_NOIMG_URL;">';
                allergensIconsBlock += '</div>';
                allergensIconsBlock += '<span class="modal-allergen-name">' + escHtml(a.name) + '</span>';
                allergensIconsBlock += '</div>';
            });
            allergensIconsBlock += '</div>';
        }

        if (withImg.length && !hasAllergenTextSection) {
            allergensIconsBlock = '<p class="modal-label">' + escHtml(t.modal_allergens || 'Alérgenos') + '</p>' + allergensIconsBlock;
        }

        const pairingHtml = p.pairing
            ? '<p class="modal-label">' + escHtml(t.modal_pairing || 'Maridaje') + '</p><div class="modal-info modal-info--pairing">' + p.pairing + '</div>'
            : '';

        let commercialChips = '';
        if (p.featured || p.recommended) {
            commercialChips = '<div class="modal-commercial-chips">';
            if (p.featured) commercialChips += commercialPillModalFeaturedHtml();
            if (p.recommended) commercialChips += commercialPillModalRecommendedHtml();
            commercialChips += '</div>';
        }

        document.getElementById('modalContent').innerHTML = `
            <button class="modal-close" onclick="closeModal()">&#x2715;</button>
            <img src="${img}" alt="${p.name}" onerror="this.onerror=null;this.src=window.BJ_NOIMG_URL;">
            <div class="modal-body">
                ${commercialChips}
                <div class="modal-name">${p.name}</div>
                <div class="modal-desc">${p.description || ''}</div>
                ${pairingHtml}
                <div class="modal-price-row">${priceHtml}</div>
                ${allergensTextBlock}
                ${allergensIconsBlock}
                ${window.ORDERS_ENABLED ? `<div class="modal-order-row"><button type="button" class="modal-add-order-btn" onclick="event.stopPropagation();addToCartFromModal(${p.id});">${escHtml((window.BJ_MENU_I18N || {}).modal_add_to_order || 'Añadir al pedido')}</button></div>` : ``}
            </div>`;

        document.getElementById('modalBg').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modalBg').classList.remove('open');
        document.body.style.overflow = '';
    }

    // Scroll spy tabs activos
    const navSections = document.querySelectorAll('[id^="cat-"], #ofertas');
    const navTabs = document.querySelectorAll('.nav-tab');
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                navTabs.forEach(function(tab) {
                    tab.classList.toggle('active', tab.getAttribute('href') === '#' + id);
                });
            }
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    navSections.forEach(function(s) { observer.observe(s); });

    // Botón scroll top
    window.addEventListener('scroll', function() {
        document.getElementById('scrollTop').classList.toggle('show', window.scrollY > 400);
    });

    // ESC: pedido, ajustes, ficha producto
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        var cart = document.getElementById('cartOverlay');
        if (cart && cart.classList.contains('open')) {
            toggleCartDrawer(false);
            return;
        }
        var so = document.getElementById('themeSettingsOverlay');
        if (so && so.classList.contains('open')) {
            if (typeof closeThemeSettings === 'function') closeThemeSettings();
            return;
        }
        closeModal();
    });
    </script>

    {{-- ── SELECTOR DE IDIOMA FLOTANTE ─────────────────────── --}}
    <?php
        $bjFlagMap = [
            'es'=>'🇪🇸','en'=>'🇬🇧','fr'=>'🇫🇷','de'=>'🇩🇪',
            'it'=>'🇮🇹','pt'=>'🇵🇹','pt_BR'=>'🇧🇷','nl'=>'🇳🇱',
            'pl'=>'🇵🇱','ru'=>'🇷🇺','zh'=>'🇨🇳','ja'=>'🇯🇵',
            'ko'=>'🇰🇷','ar'=>'🇸🇦','tr'=>'🇹🇷','sv'=>'🇸🇪',
            'da'=>'🇩🇰','nb'=>'🇳🇴','fi'=>'🇫🇮','cs'=>'🇨🇿',
            'sk'=>'🇸🇰','hu'=>'🇭🇺','ro'=>'🇷🇴','bg'=>'🇧🇬',
            'el'=>'🇬🇷','uk'=>'🇺🇦','lt'=>'🇱🇹','lv'=>'🇱🇻',
            'et'=>'🇪🇪','sl'=>'🇸🇮','id'=>'🇮🇩',
        ];
        $bjLocale   = $locale ?? 'es';
        $bjFlag     = $bjFlagMap[$bjLocale] ?? '🌐';
        $bjLocales  = $availableLocales ?? ['es'];
    ?>

    <div id="langWidget" style="position:fixed;top:14px;right:16px;z-index:9999;">
        <button id="langBtn" type="button"
                style="display:flex;align-items:center;gap:6px;padding:6px 12px;
                       background:var(--surface);border:1px solid var(--prod-border);
                       border-radius:50px;cursor:pointer;font-weight:700;
                       color:var(--text);box-shadow:0 2px 12px rgba(0,0,0,.25);
                       backdrop-filter:blur(8px);transition:box-shadow .2s;"
                aria-label="{{ __('public_menu.lang_change_aria') }}">
            <span style="font-size:20px;line-height:1;"><?= $bjFlag ?></span>
            <span style="font-size:11px;letter-spacing:.06em;"><?= strtoupper($bjLocale) ?></span>
            <span style="font-size:9px;opacity:.5;">▼</span>
        </button>

        <div id="langMenu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;
             background:var(--surface);border:1px solid var(--prod-border);
             border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.35);
             min-width:150px;overflow:hidden;">
            <?php foreach($bjLocales as $bjLoc):
                $bjLf  = $bjFlagMap[$bjLoc] ?? '🌐';
                $bjAct = ($bjLoc === $bjLocale);
            ?>
            <a href="<?= e(request()->fullUrlWithQuery(['lang' => $bjLoc])) ?>"
               style="display:flex;align-items:center;gap:10px;padding:10px 14px;
                      font-size:13px;font-weight:<?= $bjAct ? '700' : '500' ?>;
                      color:<?= $bjAct ? 'var(--gold)' : 'var(--text)' ?>;
                      text-decoration:none;background:<?= $bjAct ? 'var(--surface-el)' : 'transparent' ?>;"
               onmouseover="this.style.background='var(--surface-el)'"
               onmouseout="this.style.background='<?= $bjAct ? 'var(--surface-el)' : 'transparent' ?>'">
                <span style="font-size:20px;line-height:1;"><?= $bjLf ?></span>
                <span><?= strtoupper($bjLoc) ?></span>
                <?= $bjAct ? '<span style="margin-left:auto;font-size:11px;">✓</span>' : '' ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function() {
        var btn  = document.getElementById('langBtn');
        var menu = document.getElementById('langMenu');
        if (!btn || !menu) return;
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function() {
            menu.style.display = 'none';
        });
    })();
    </script>
</body>
</html>
