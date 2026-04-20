<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <script>
        (function () {
            var K = 'bjCartaTheme';
            function stored() {
                try {
                    var t = localStorage.getItem(K) || 'light';
                    if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'light';
                    return t;
                } catch (e) { return 'dark'; }
            }
            function effective(pref) {
                if (pref === 'system') {
                    try {
                        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    } catch (e) { return 'light'; }
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
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <title>{{ config('app.name', 'Laravel') }} · Admin</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600;700&display=swap">
        @php
            // En este proyecto mix-manifest no tiene hashes; añadimos versionado para evitar caché agresiva del navegador.
            $adminCssV = @filemtime(public_path('css/app.css')) ?: time();
            $adminJsV  = @filemtime(public_path('js/app.js')) ?: time();
        @endphp
        <link rel="stylesheet" href="{{ mix('css/app.css') }}?v={{ $adminCssV }}">

        @livewireStyles

        <script src="{{ mix('js/app.js') }}?v={{ $adminJsV }}" defer></script>

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
                                        class="admin-cta-trigger flex items-center gap-3 cursor-pointer rounded-xl border py-2 pl-3 pr-3 min-w-0 sm:min-w-[17rem] shadow-sm transition-colors select-none"
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
                        var t = localStorage.getItem(THEME_KEY) || 'light';
                        if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'light';
                        return t;
                    } catch (e) {
                        return 'light';
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
