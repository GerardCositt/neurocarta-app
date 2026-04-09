<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <script>
        (function () {
            var K = 'bjCartaTheme';
            function stored() {
                try {
                    var t = localStorage.getItem(K) || 'dark';
                    if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'dark';
                    return t;
                } catch (e) { return 'dark'; }
            }
            function effective(pref) {
                if (pref === 'system') {
                    try {
                        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    } catch (e) { return 'dark'; }
                }
                return pref;
            }
            var pref = stored();
            document.documentElement.setAttribute('data-theme', pref);
            document.documentElement.setAttribute('data-effective-theme', effective(pref));
            // Restaurar estado plegado del sidebar ANTES del primer paint
            try {
                var _sc = localStorage.getItem('bjAdminSidebarCollapsed');
                document.documentElement.setAttribute('data-admin-sidebar', _sc === '1' ? 'collapsed' : 'expanded');
            } catch(e) {
                document.documentElement.setAttribute('data-admin-sidebar', 'expanded');
            }
        })();
        </script>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} · Admin</title>

        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
        @php
            // En este proyecto mix-manifest no tiene hashes; añadimos versionado para evitar caché agresiva del navegador.
            $adminCssV = @filemtime(public_path('css/app.css')) ?: time();
            $adminJsV  = @filemtime(public_path('js/app.js')) ?: time();
        @endphp
        <link rel="stylesheet" href="{{ mix('css/app.css') }}?v={{ $adminCssV }}">

        @livewireStyles

        <script src="{{ mix('js/app.js') }}?v={{ $adminJsV }}" defer></script>

        <style>
            [x-cloak] { display: none !important; }

            body.admin-shell {
                font-family: 'Inter', system-ui, sans-serif;
                background: #f5f6fa;
                color: #111827;
            }
            details.admin-user-menu > summary { list-style: none; }
            details.admin-user-menu > summary::-webkit-details-marker { display: none; }
            #restaurantHeaderPicker > summary { list-style: none; }
            #restaurantHeaderPicker > summary::-webkit-details-marker { display: none; }
            #restaurantHeaderPicker > summary::marker { display: none; }
            #actions-menu-products > summary { list-style: none; }
            #actions-menu-products > summary::-webkit-details-marker { display: none; }
            #actions-menu-products > summary::marker { display: none; }

            html[data-effective-theme="dark"] { color-scheme: dark; }
            html[data-effective-theme="dark"] body.admin-shell {
                background: #0f1419;
                color: #e5e7eb;
            }

            /* Sidebar */
            .admin-sidebar {
                background: #ffffff;
                border-right: 1px solid #f3f4f6;
            }
            html[data-effective-theme="dark"] .admin-sidebar {
                background: #111827;
                border-right-color: rgba(255, 255, 255, 0.08);
            }

            /* Saldo IA: siempre pie del viewport (no del scroll); fuentes aquí para no depender del build de Tailwind */
            .admin-ai-credits-fixed {
                position: fixed !important;
                left: 0 !important;
                right: auto !important;
                bottom: 0 !important;
                top: auto !important;
                width: 14rem !important;
                max-width: 100vw;
                z-index: 40 !important;
                box-sizing: border-box;
                background: #ffffff;
                border-top: 1px solid #f3f4f6;
                box-shadow: 0 -6px 16px rgba(15, 23, 42, 0.06);
                padding-bottom: calc(0.625rem + env(safe-area-inset-bottom, 0px));
            }
            .admin-ai-credits-fixed .admin-ai-credits-title {
                font-size: 0.875rem !important; /* 14px */
                line-height: 1.3;
                font-weight: 600;
            }
            .admin-ai-credits-fixed .admin-ai-credits-body {
                font-size: 0.8125rem !important; /* 13px */
                line-height: 1.4;
            }
            .admin-ai-credits-fixed .admin-ai-credits-body--demo {
                font-size: 0.75rem !important; /* 12px */
                line-height: 1.35;
            }
            .admin-ai-credits-fixed .admin-ai-credits-hint {
                font-size: 0.75rem !important;
                line-height: 1.35;
            }
            html[data-effective-theme="dark"] .admin-ai-credits-fixed {
                background: #111827;
                border-top-color: rgba(255, 255, 255, 0.08);
                box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.35);
            }
            html[data-effective-theme="dark"] .admin-ai-credits-fixed .admin-ai-credits-zero-panel {
                background: rgba(190, 18, 60, 0.18) !important;
                border-color: rgba(251, 113, 133, 0.35) !important;
            }
            html[data-effective-theme="dark"] .admin-ai-credits-fixed .admin-ai-credits-zero-panel .text-rose-900 {
                color: #fecdd3 !important;
            }
            html[data-effective-theme="dark"] .admin-ai-credits-fixed .admin-ai-credits-zero-panel .text-rose-800 {
                color: #fda4af !important;
            }
            .admin-sidebar-header { border-bottom: 1px solid #f3f4f6; }
            html[data-effective-theme="dark"] .admin-sidebar-header {
                border-bottom-color: rgba(255, 255, 255, 0.08);
            }

            /* Sidebar plegable en desktop: solo iconos */
            html[data-admin-sidebar="collapsed"] #adminSidebar {
                width: 4.25rem !important; /* ~68px */
                max-width: 4.25rem !important;
                overflow-x: hidden;
            }
            /* En desktop (≥640px) el sidebar va en flujo normal (static), no fixed.
               sm:static de Tailwind puede no estar compilado si el build está desactualizado. */
            @media (min-width: 640px) {
                #adminSidebar {
                    position: static !important;
                    z-index: auto !important;
                    transform: none !important;
                    width: 14rem !important;      /* w-56 */
                    max-width: none !important;
                    flex-shrink: 0 !important;
                    overflow-x: visible;
                }
                html[data-admin-sidebar="collapsed"] #adminSidebar {
                    width: 4.25rem !important;
                    max-width: 4.25rem !important;
                    overflow-x: hidden;
                }
            }

            /* Asegurar fondo opaco del contenedor del sidebar */
            #adminSidebar { background: #ffffff; }
            html[data-effective-theme="dark"] #adminSidebar { background: #111827; }
            html[data-admin-sidebar="collapsed"] #adminSidebar .admin-sidebar-header,
            html[data-admin-sidebar="collapsed"] #adminSidebar nav {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            html[data-admin-sidebar="collapsed"] #adminSidebar .admin-brand-title,
            html[data-admin-sidebar="collapsed"] #adminSidebar .admin-brand-sub,
            html[data-admin-sidebar="collapsed"] #adminSidebar .admin-nav-label {
                display: none !important;
            }
            html[data-admin-sidebar="collapsed"] #adminSidebar nav a,
            html[data-admin-sidebar="collapsed"] #adminSidebar nav button {
                justify-content: center !important;
                gap: 0 !important;
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
            html[data-admin-sidebar="collapsed"] #adminSidebar nav svg {
                margin-left: auto;
                margin-right: auto;
            }
            /* En sidebar plegado: Ajustes no se puede expandir (ocultar sub-items) */
            html[data-admin-sidebar="collapsed"] #adminSidebar nav details > div {
                display: none !important;
            }
            /* Credits banner: mantiene ancho completo aunque el sidebar esté plegado */
            html[data-admin-sidebar="collapsed"] .admin-ai-credits-fixed {
                width: 14rem !important;
            }

            /* Tablas admin: columnas sticky (para que "Nombre" siempre se vea) */
            .admin-sticky-col {
                position: sticky;
                z-index: 20;
                background: #ffffff;
            }
            .admin-sticky-hdr { z-index: 30; }
            html[data-effective-theme="dark"] .admin-sticky-col {
                background: #1a2235;
            }
            /* Offsets (productos): checkbox + drag + imagen + nombre + categoría */
            .admin-sticky-left-0 { left: 0; }
            .admin-sticky-left-4 { left: 4rem; }      /* after checkbox col */
            .admin-sticky-left-7 { left: 7rem; }      /* after checkbox + drag */
            .admin-sticky-left-125 { left: 12.5rem; } /* after checkbox + drag + image */
            .admin-sticky-left-45 { left: 45.25rem; } /* after checkbox + drag + image + nombre (500px + px-3×2) */
            html[data-effective-theme="dark"] .admin-sidebar .admin-brand-title { color: #f3f4f6 !important; }
            html[data-effective-theme="dark"] .admin-sidebar .admin-brand-sub { color: #9ca3af !important; }
            html[data-effective-theme="dark"] .admin-sidebar nav a {
                color: #d1d5db;
            }
            html[data-effective-theme="dark"] .admin-sidebar nav a:hover {
                background-color: rgba(255, 255, 255, 0.06);
                color: #f9fafb;
            }
            /* Ítem activo del menú (sustituye ámbar por gris pizarra) */
            .admin-nav-active {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 600;
            }
            html[data-effective-theme="dark"] .admin-sidebar .admin-nav-active {
                background-color: rgba(148, 163, 184, 0.16) !important;
                color: #f8fafc !important;
            }

            /* Categorías (admin): columna Ocultar ~3 cm más hacia la izquierda */
            .category-col-hide {
                margin-right: 3cm;
            }
            html[data-effective-theme="dark"] .admin-sidebar button {
                color: #d1d5db;
            }
            html[data-effective-theme="dark"] .admin-sidebar button:hover {
                background-color: rgba(255, 255, 255, 0.06);
                color: #f9fafb;
            }
            html[data-effective-theme="dark"] .admin-sidebar #qr-panel .border-gray-100,
            html[data-effective-theme="dark"] .admin-sidebar #appearance-panel .border-gray-100 {
                border-color: rgba(255, 255, 255, 0.1) !important;
            }
            html[data-effective-theme="dark"] .admin-sidebar #qr-panel a.text-gray-400 { color: #9ca3af !important; }
            html[data-effective-theme="dark"] .admin-sidebar #appearance-panel .text-gray-500 { color: #9ca3af !important; }

            /* Cabecera de página y barra superior */
            html[data-effective-theme="dark"] .admin-page-header h2,
            html[data-effective-theme="dark"] .admin-page-header .text-xl { color: #f3f4f6 !important; }

            /* Selector de tema */
            .admin-theme-switch { background: #fff; }
            html[data-effective-theme="dark"] .admin-theme-switch {
                border-color: rgba(255, 255, 255, 0.12) !important;
                background: #1a2235;
            }
            html[data-effective-theme="dark"] .admin-theme-switch .border-gray-200 {
                border-color: rgba(255, 255, 255, 0.1) !important;
            }
            html[data-effective-theme="dark"] .dark-theme-label { color: #9ca3af !important; }
            .admin-theme-btn {
                background: transparent;
                color: #4b5563;
            }
            .admin-theme-btn:hover { background: rgba(0, 0, 0, 0.04); }
            .admin-theme-btn[aria-pressed="true"] {
                background: rgba(245, 158, 11, 0.15);
                color: #b45309;
                font-weight: 600;
            }
            html[data-effective-theme="dark"] .admin-theme-btn {
                color: #d1d5db;
            }
            html[data-effective-theme="dark"] .admin-theme-btn:hover {
                background: rgba(255, 255, 255, 0.06);
            }
            html[data-effective-theme="dark"] .admin-theme-btn[aria-pressed="true"] {
                background: rgba(245, 158, 11, 0.22);
                color: #fcd34d;
            }

            /* Menú cuenta */
            html[data-effective-theme="dark"] .admin-user-menu > summary {
                border-color: rgba(255, 255, 255, 0.12);
                background: #1a2235;
            }
            html[data-effective-theme="dark"] .admin-user-menu > summary:hover {
                border-color: rgba(245, 158, 11, 0.35);
                background: rgba(245, 158, 11, 0.08);
            }
            html[data-effective-theme="dark"] .admin-user-menu > summary .text-gray-800 { color: #f3f4f6 !important; }
            html[data-effective-theme="dark"] .admin-user-menu > summary .text-gray-400 { color: #9ca3af !important; }
            html[data-effective-theme="dark"] .admin-user-menu .admin-user-dropdown {
                background: #1a2235 !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45);
            }
            html[data-effective-theme="dark"] .admin-user-menu .admin-user-dropdown-head {
                border-color: rgba(255, 255, 255, 0.08) !important;
                background: rgba(0, 0, 0, 0.2) !important;
            }
            html[data-effective-theme="dark"] .admin-user-menu .admin-user-dropdown-head .text-gray-900 { color: #f3f4f6 !important; }
            html[data-effective-theme="dark"] .admin-user-menu .admin-user-dropdown-head .text-gray-500 { color: #9ca3af !important; }
            html[data-effective-theme="dark"] .admin-user-menu .admin-user-dropdown button { color: #e5e7eb !important; }
            html[data-effective-theme="dark"] .admin-user-menu .admin-user-dropdown button:hover {
                background-color: rgba(239, 68, 68, 0.15) !important;
                color: #fca5a5 !important;
            }

            /* Contenido principal (Livewire) */
            html[data-effective-theme="dark"] .admin-main .bg-white { background-color: #1a2235 !important; }
            html[data-effective-theme="dark"] .admin-main .bg-gray-50 { background-color: rgba(255, 255, 255, 0.04) !important; }
            html[data-effective-theme="dark"] .admin-main .border-gray-100,
            html[data-effective-theme="dark"] .admin-main .border-gray-200 {
                border-color: rgba(255, 255, 255, 0.08) !important;
            }
            html[data-effective-theme="dark"] .admin-main .text-gray-800,
            html[data-effective-theme="dark"] .admin-main .text-gray-900 { color: #f3f4f6 !important; }
            html[data-effective-theme="dark"] .admin-main .text-gray-700 { color: #e5e7eb !important; }
            html[data-effective-theme="dark"] .admin-main .text-gray-600,
            html[data-effective-theme="dark"] .admin-main .text-gray-500 { color: #9ca3af !important; }
            html[data-effective-theme="dark"] .admin-main .text-gray-400 { color: #6b7280 !important; }
            html[data-effective-theme="dark"] .admin-main .hover\:bg-gray-50:hover { background-color: rgba(255, 255, 255, 0.05) !important; }
            html[data-effective-theme="dark"] .admin-main input[type="text"],
            html[data-effective-theme="dark"] .admin-main input[type="search"],
            html[data-effective-theme="dark"] .admin-main input[type="number"],
            html[data-effective-theme="dark"] .admin-main input[type="email"],
            html[data-effective-theme="dark"] .admin-main input[type="password"],
            html[data-effective-theme="dark"] .admin-main input[type="date"],
            html[data-effective-theme="dark"] .admin-main input[type="url"],
            html[data-effective-theme="dark"] .admin-main select,
            html[data-effective-theme="dark"] .admin-main textarea {
                background-color: #111827 !important;
                border-color: rgba(255, 255, 255, 0.12) !important;
                color: #e5e7eb !important;
            }
            html[data-effective-theme="dark"] .admin-main .bg-green-50 {
                background-color: rgba(34, 197, 94, 0.12) !important;
            }
            html[data-effective-theme="dark"] .admin-main .border-green-200 {
                border-color: rgba(34, 197, 94, 0.35) !important;
            }
            html[data-effective-theme="dark"] .admin-main .text-green-800 { color: #86efac !important; }

            /* Banners semánticos — referencia: docs/gestion-banners.md */
            .admin-banner {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 0.875rem 1rem;
                margin-bottom: 1rem;
                border-radius: 0.75rem;
                border: 1px solid transparent;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.35);
            }
            .admin-banner__icon {
                flex-shrink: 0;
                font-size: 1.125rem;
                line-height: 1.5;
            }
            .admin-banner__content {
                flex: 1;
                min-width: 0;
                font-size: 0.875rem;
                line-height: 1.5;
            }
            .admin-banner--info {
                background: #e8f4fc;
                border-color: #7eb8d6;
                color: #0c4a6e;
            }
            .admin-banner--success {
                background: #ecf8f0;
                border-color: #7cc9a0;
                color: #14532d;
            }
            .admin-banner--warning {
                background: #fffbeb;
                border-color: #e6c35c;
                color: #78350f;
            }
            .admin-banner--danger {
                background: #fef2f2;
                border-color: #f0a8a8;
                color: #7f1d1d;
            }

            .admin-inset {
                border-radius: 0.75rem;
                border: 1px solid transparent;
            }
            .admin-inset--info {
                background: #e8f4fc;
                border-color: transparent;
                color: #0c4a6e;
            }
            .admin-inset--warning {
                background: #fffbeb;
                border-color: #e6c35c;
                color: #78350f;
            }
            .admin-inset--danger {
                background: #fef2f2;
                border-color: #f0a8a8;
                color: #7f1d1d;
            }

            /* Productos: selección masiva — tooltip neutro (gris / slate) */
            .admin-bulk-panel {
                margin-bottom: 1rem;
                position: relative;
                z-index: 1;
            }
            /* El panel completo necesita z-index para quedar por encima de la tabla sticky */
            .admin-bulk-panel {
                position: relative;
                z-index: 35;
            }
            .admin-bulk-tooltip {
                position: relative;
                display: inline-block;
                z-index: 40;
            }
            .admin-bulk-tooltip__trigger {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                margin: 0;
                padding: 0.4rem 0.9rem;
                border-radius: 9999px;
                font-size: 0.8125rem;
                font-weight: 600;
                font-family: inherit;
                color: #334155;
                background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
                border: 1px solid #cbd5e1;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                cursor: help;
                user-select: none;
                transition: filter 0.15s ease, box-shadow 0.15s ease;
            }
            .admin-bulk-tooltip__trigger:hover,
            .admin-bulk-tooltip__trigger:focus {
                outline: none;
                filter: brightness(1.02);
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
            }
            .admin-bulk-tooltip__trigger:focus-visible {
                box-shadow: 0 0 0 2px #fff, 0 0 0 4px #64748b;
            }
            .admin-bulk-tooltip__icon {
                flex-shrink: 0;
                opacity: 0.85;
                width: 10px !important;
                height: 10px !important;
            }
            /* A la derecha del chip: el cursor puede pasar al texto sin perder hover */
            .admin-bulk-tooltip__bubble {
                position: absolute;
                left: calc(100% + 0.5rem);
                top: 50%;
                bottom: auto;
                z-index: 200;
                width: min(19rem, calc(100vw - 2rem - 8rem));
                padding: 0.65rem 0.85rem;
                border-radius: 0.65rem;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                box-shadow:
                    0 4px 12px rgba(0, 0, 0, 0.08),
                    0 8px 24px rgba(15, 23, 42, 0.08);
                font-size: 0.75rem;
                line-height: 1.45;
                color: #475569;
                text-align: left;
                visibility: hidden;
                opacity: 0;
                pointer-events: none;
                transform: translateY(-50%) translateX(-4px);
                transition: opacity 0.14s ease, visibility 0.14s ease, transform 0.14s ease;
            }
            .admin-bulk-tooltip__bubble::after {
                content: '';
                position: absolute;
                left: -5px;
                top: 50%;
                margin-top: -4px;
                width: 8px;
                height: 8px;
                background: #ffffff;
                border-left: 1px solid #e2e8f0;
                border-bottom: 1px solid #e2e8f0;
                transform: rotate(45deg);
            }
            .admin-bulk-tooltip:hover .admin-bulk-tooltip__bubble,
            .admin-bulk-tooltip:focus-within .admin-bulk-tooltip__bubble {
                visibility: visible;
                opacity: 1;
                pointer-events: auto;
                transform: translateY(-50%) translateX(0);
            }
            .admin-bulk-tooltip__line {
                margin: 0 0 0.45rem 0;
            }
            .admin-bulk-tooltip__line:last-child {
                margin-bottom: 0;
            }
            .admin-bulk-tooltip__line--fine {
                padding-top: 0.45rem;
                border-top: 1px solid #e2e8f0;
                font-size: 0.7rem;
                color: #64748b;
            }
            .admin-bulk-tooltip--below .admin-bulk-tooltip__bubble {
                left: 0;
                top: calc(100% + 0.55rem);
                bottom: auto;
                transform: translateY(-4px);
            }
            .admin-bulk-tooltip--below .admin-bulk-tooltip__bubble::after {
                left: 1rem;
                top: -5px;
                margin-top: 0;
                border-left: 1px solid #e2e8f0;
                border-top: 1px solid #e2e8f0;
                border-bottom: 0;
                transform: rotate(45deg);
            }
            .admin-bulk-tooltip--below:hover .admin-bulk-tooltip__bubble,
            .admin-bulk-tooltip--below:focus-within .admin-bulk-tooltip__bubble {
                transform: translateY(0);
            }
            @media (prefers-reduced-motion: reduce) {
                .admin-bulk-tooltip__bubble {
                    transition: none;
                    transform: translateY(-50%);
                }
                .admin-bulk-tooltip:hover .admin-bulk-tooltip__bubble,
                .admin-bulk-tooltip:focus-within .admin-bulk-tooltip__bubble {
                    transform: translateY(-50%);
                }
                .admin-bulk-tooltip--below .admin-bulk-tooltip__bubble,
                .admin-bulk-tooltip--below:hover .admin-bulk-tooltip__bubble,
                .admin-bulk-tooltip--below:focus-within .admin-bulk-tooltip__bubble {
                    transform: translateY(0);
                }
            }

            .admin-row-info {
                background: #e8f4fc !important;
                border-color: #bae6fd !important;
            }
            .admin-row-warning {
                background: #f8fafc !important;
                border-color: #e2e8f0 !important;
            }

            html[data-effective-theme="dark"] .admin-main .admin-banner--info {
                background: rgba(56, 189, 248, 0.12);
                border-color: rgba(56, 189, 248, 0.35);
                color: #bae6fd;
            }
            html[data-effective-theme="dark"] .admin-main .admin-banner--success {
                background: rgba(34, 197, 94, 0.14);
                border-color: rgba(74, 222, 128, 0.35);
                color: #86efac;
            }
            html[data-effective-theme="dark"] .admin-main .admin-banner--warning {
                background: rgba(245, 158, 11, 0.14);
                border-color: rgba(251, 191, 36, 0.4);
                color: #fcd34d;
            }
            html[data-effective-theme="dark"] .admin-main .admin-banner--danger {
                background: rgba(239, 68, 68, 0.14);
                border-color: rgba(248, 113, 113, 0.4);
                color: #fecaca;
            }
            html[data-effective-theme="dark"] .admin-main .admin-inset--info {
                background: rgba(56, 189, 248, 0.1);
                border-color: transparent;
                color: #bae6fd;
            }
            html[data-effective-theme="dark"] .admin-main .admin-inset--warning {
                background: rgba(245, 158, 11, 0.12);
                border-color: rgba(251, 191, 36, 0.35);
                color: #fcd34d;
            }
            html[data-effective-theme="dark"] .admin-main .admin-inset--danger {
                background: rgba(239, 68, 68, 0.12);
                border-color: rgba(248, 113, 113, 0.35);
                color: #fecaca;
            }
            html[data-effective-theme="dark"] .admin-main .admin-inset--info .text-gray-800,
            html[data-effective-theme="dark"] .admin-main .admin-inset--info .text-gray-700 {
                color: #e0f2fe !important;
            }
            html[data-effective-theme="dark"] .admin-main .admin-inset--danger .text-gray-700 {
                color: #fecaca !important;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-tooltip__trigger {
                color: #e2e8f0;
                background: linear-gradient(180deg, #334155 0%, #1e293b 100%);
                border-color: #475569;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-tooltip__trigger:focus-visible {
                box-shadow: 0 0 0 2px #111827, 0 0 0 4px #94a3b8;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-tooltip__bubble {
                background: #1e293b;
                border-color: #475569;
                color: #cbd5e1;
                box-shadow:
                    0 8px 28px rgba(0, 0, 0, 0.45),
                    0 0 0 1px rgba(148, 163, 184, 0.12);
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-tooltip__bubble::after {
                background: #1e293b;
                border-right-color: #475569;
                border-bottom-color: #475569;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-tooltip--below .admin-bulk-tooltip__bubble::after {
                border-top-color: #475569;
                border-right-color: transparent;
                border-bottom-color: transparent;
                border-left-color: #475569;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-tooltip__line--fine {
                border-top-color: rgba(148, 163, 184, 0.35);
                color: #94a3b8;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-panel__active {
                border-color: rgba(255, 255, 255, 0.1) !important;
                background: #1f2937 !important;
                box-shadow: none !important;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-panel__active .text-gray-800 {
                color: #f3f4f6 !important;
            }
            html[data-effective-theme="dark"] .admin-main .admin-bulk-panel__active .text-gray-600 {
                color: #9ca3af !important;
            }
            html[data-effective-theme="dark"] .admin-main .admin-row-info {
                background: rgba(56, 189, 248, 0.12) !important;
                border-color: rgba(56, 189, 248, 0.25) !important;
            }
            html[data-effective-theme="dark"] .admin-main .admin-row-warning {
                background: rgba(148, 163, 184, 0.1) !important;
                border-color: rgba(148, 163, 184, 0.22) !important;
            }

            /* Pie del layout admin */
            .admin-footer {
                background: #fff;
                border-color: #f3f4f6;
            }
            html[data-effective-theme="dark"] .admin-footer {
                background: #111827;
                border-top-color: rgba(255, 255, 255, 0.08) !important;
                color: #9ca3af !important;
            }
            html[data-effective-theme="dark"] .admin-footer a { color: #9ca3af !important; }
            html[data-effective-theme="dark"] .admin-footer a:hover { color: #d1d5db !important; }
        </style>
    </head>
    <body class="antialiased admin-shell">
        <x-jet-banner />

        <div class="flex min-h-screen w-full">

            @livewire('navigation-menu')

            <div class="flex-1 flex flex-col min-w-0 w-full">

                @if (isset($header) || auth()->check())
                    <div class="admin-page-header px-4 sm:px-6 lg:px-8 pt-4 sm:pt-8 pb-2 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <button type="button"
                                    class="sm:hidden inline-flex items-center gap-2 mb-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm"
                                    onclick="openAdminSidebar()">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                Menú
                            </button>
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>

                        @auth
                            <div class="flex flex-wrap items-center justify-end gap-3 flex-shrink-0 lg:ml-4 lg:pt-0.5">
                                @livewire('admin.restaurant-switcher', ['mode' => 'header'])
                                <details class="relative admin-user-menu text-left self-end lg:self-start">
                                    <summary
                                        class="flex items-center gap-3 cursor-pointer rounded-xl border border-gray-200 bg-white py-2 pl-3 pr-3 min-w-0 sm:min-w-[17rem] shadow-sm hover:border-gray-300 hover:bg-gray-50 transition-colors select-none"
                                        aria-label="{{ __('admin.layout.account_aria', ['name' => Auth::user()->name]) }}">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-700 flex-shrink-0">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                        <div class="hidden sm:block text-left min-w-0 flex-1 max-w-[15rem]">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ Auth::user()->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </summary>
                                    <div class="admin-user-dropdown absolute right-0 mt-2 w-64 rounded-xl border border-gray-100 bg-white shadow-lg z-50 overflow-hidden">
                                        <div class="admin-user-dropdown-head px-4 py-3 border-b border-gray-100 bg-gray-50/80">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                        </div>
                                        <div class="p-2 space-y-2">
                                            <div class="px-3 pt-1 pb-2">
                                                <label for="admin-panel-locale" class="block text-xs font-medium text-gray-500 mb-1">{{ __('admin.locale.label') }}</label>
                                                <form method="POST" action="{{ route('user.locale') }}" id="admin-locale-form">
                                                    @csrf
                                                    <select id="admin-panel-locale" name="locale"
                                                            onchange="document.getElementById('admin-locale-form').submit()"
                                                            class="w-full rounded-lg border border-gray-200 text-sm text-gray-800 py-2 pl-2 pr-8 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                                                        @foreach (config('app.admin_locales', ['es', 'en']) as $loc)
                                                            <option value="{{ $loc }}" @if($loc === (auth()->user()->locale ?? 'es')) selected @endif>
                                                                {{ __('admin.locale.' . $loc) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </div>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors text-left">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                                    </svg>
                                                    {{ __('admin.layout.logout') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        @endauth
                    </div>
                @endif

                <main class="admin-main flex-1 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 min-w-0" data-flatpickr-root>
                    {{ $slot }}
                </main>

                <div class="admin-footer text-center text-xs text-gray-400 py-4 border-t border-gray-100 bg-white">
                    &copy; <script>document.write(new Date().getFullYear())</script>
                    <span class="text-gray-500">{{ config('app.name') }}</span>
                    <span class="text-gray-300 mx-1">·</span>
                    <span>{{ __('admin.layout.footer') }}</span>
                </div>

            </div>
        </div>

        @stack('modals')

        @livewireScripts

        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            (function () {
                var THEME_KEY = 'bjCartaTheme';

                function getStoredTheme() {
                    try {
                        var t = localStorage.getItem(THEME_KEY) || 'dark';
                        if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'dark';
                        return t;
                    } catch (e) {
                        return 'dark';
                    }
                }

                function effectiveTheme(pref) {
                    if (pref === 'system') {
                        try {
                            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                        } catch (e) {
                            return 'dark';
                        }
                    }
                    return pref;
                }

                function applyAdminTheme(mode, save) {
                    if (mode !== 'light' && mode !== 'dark' && mode !== 'system') mode = 'dark';
                    if (save !== false) {
                        try {
                            localStorage.setItem(THEME_KEY, mode);
                        } catch (e) {}
                    }
                    document.documentElement.setAttribute('data-theme', mode);
                    document.documentElement.setAttribute('data-effective-theme', effectiveTheme(mode));
                    document.querySelectorAll('[data-admin-theme]').forEach(function (btn) {
                        var v = btn.getAttribute('data-admin-theme');
                        btn.setAttribute('aria-pressed', v === mode ? 'true' : 'false');
                    });
                }

                window.applyAdminTheme = applyAdminTheme;

                document.addEventListener('DOMContentLoaded', function () {
                    applyAdminTheme(getStoredTheme(), false);

                    // En móvil, el sidebar debe arrancar cerrado siempre.
                    try {
                        if (window.innerWidth < 640 && typeof closeAdminSidebar === 'function') {
                            closeAdminSidebar();
                        }
                    } catch (e) {}

                    // En desktop, restaurar estado plegado del sidebar.
                    try {
                        const v = localStorage.getItem(adminSidebarCollapsedKey());
                        if (window.innerWidth >= 640) {
                            applyAdminSidebarCollapsed(v === '1', false);
                        }
                    } catch (e) {}

                    var userMenu = document.querySelector('details.admin-user-menu');
                    if (userMenu) {
                        document.addEventListener('click', function (e) {
                            if (!userMenu.open) return;
                            if (!userMenu.contains(e.target)) {
                                userMenu.open = false;
                            }
                        });
                    }

                    var restaurantPicker = document.getElementById('restaurantHeaderPicker');
                    if (restaurantPicker) {
                        document.addEventListener('click', function (e) {
                            if (!restaurantPicker.open) return;
                            if (!restaurantPicker.contains(e.target)) {
                                restaurantPicker.open = false;
                            }
                        });
                    }

                    document.querySelectorAll('[data-admin-theme]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            applyAdminTheme(btn.getAttribute('data-admin-theme'), true);
                        });
                    });

                    window.addEventListener('storage', function (e) {
                        if (e.key === THEME_KEY && e.newValue) {
                            if (e.newValue === 'light' || e.newValue === 'dark' || e.newValue === 'system') {
                                applyAdminTheme(e.newValue, false);
                            }
                        }
                    });

                    try {
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                            if (getStoredTheme() === 'system') {
                                document.documentElement.setAttribute('data-effective-theme', effectiveTheme('system'));
                            }
                        });
                    } catch (e) {}
                });
            })();

            function initSidebarQR() {
                const el = document.getElementById('sidebar-qr');
                if (!el) return;
                // Regenerar siempre por si MENU_URL cambia al cambiar de restaurante.
                el.innerHTML = '';
                new QRCode(el, {
                    text: typeof MENU_URL !== 'undefined' ? MENU_URL : window.location.origin,
                    width: 160,
                    height: 160,
                    colorDark: '#111827',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
            function toggleSidebarQR() {
                const panel = document.getElementById('qr-panel');
                if (!panel) return;
                const hidden = panel.classList.toggle('hidden');
                if (!hidden) initSidebarQR();
            }
            function adminSidebarCollapsedKey() { return 'bjAdminSidebarCollapsed'; }
            function applyAdminSidebarCollapsed(collapsed, save) {
                const isCollapsed = collapsed === true || collapsed === '1' || collapsed === 1;
                if (save !== false) {
                    try { localStorage.setItem(adminSidebarCollapsedKey(), isCollapsed ? '1' : '0'); } catch (e) {}
                }
                document.documentElement.setAttribute('data-admin-sidebar', isCollapsed ? 'collapsed' : 'expanded');
                // Rotar icono del botón
                var icon = document.getElementById('sidebarCollapseBtnIcon');
                if (icon) {
                    icon.style.transform = isCollapsed ? 'rotate(180deg)' : '';
                }
            }
            function toggleAdminSidebarCollapsed() {
                const cur = document.documentElement.getAttribute('data-admin-sidebar') === 'collapsed';
                applyAdminSidebarCollapsed(!cur, true);
            }
            function openAdminSidebar() {
                const sb = document.getElementById('adminSidebar');
                const ov = document.getElementById('adminSidebarOverlay');
                if (sb) sb.classList.remove('-translate-x-full');
                if (ov) ov.classList.remove('hidden');
                try { document.body.style.overflow = 'hidden'; } catch (e) {}
            }
            function closeAdminSidebar() {
                const sb = document.getElementById('adminSidebar');
                const ov = document.getElementById('adminSidebarOverlay');
                if (sb) sb.classList.add('-translate-x-full');
                if (ov) ov.classList.add('hidden');
                try { document.body.style.overflow = ''; } catch (e) {}
            }
            function toggleSidebarAppearance() {
                const panel = document.getElementById('appearance-panel');
                if (!panel) return;
                panel.classList.toggle('hidden');
            }
            function downloadSidebarQR() {
                const canvas = document.querySelector('#sidebar-qr canvas');
                if (!canvas) return;
                const a = document.createElement('a');
                a.download = (typeof QR_FILENAME !== 'undefined' ? QR_FILENAME : 'qr-carta.png');
                a.href = canvas.toDataURL('image/png');
                a.click();
            }

            (function () {
                var MAX_MS = 5000;
                function hideBanner(el) {
                    var reduce = false;
                    try {
                        reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    } catch (e) {}
                    function remove() {
                        if (el && el.parentNode) {
                            el.parentNode.removeChild(el);
                        }
                    }
                    if (reduce) {
                        remove();
                        return;
                    }
                    el.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-8px)';
                    setTimeout(remove, 360);
                }
                function schedule(el) {
                    if (el.getAttribute('data-auto-dismiss-scheduled') === '1') {
                        return;
                    }
                    el.setAttribute('data-auto-dismiss-scheduled', '1');
                    var raw = el.getAttribute('data-auto-dismiss');
                    var ms = parseInt(raw, 10);
                    if (isNaN(ms) || ms < 0) {
                        ms = MAX_MS;
                    }
                    if (ms > MAX_MS) {
                        ms = MAX_MS;
                    }
                    setTimeout(function () {
                        hideBanner(el);
                    }, ms);
                }
                function scan() {
                    document.querySelectorAll('.admin-banner[data-auto-dismiss]').forEach(function (el) {
                        schedule(el);
                    });
                }
                document.addEventListener('DOMContentLoaded', scan);
                document.addEventListener('livewire:load', function () {
                    scan();
                    document.addEventListener('livewire:update', scan);
                });
            })();
        </script>
        @stack('scripts')
    </body>
</html>
