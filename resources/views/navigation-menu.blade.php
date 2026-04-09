<div>
<div id="adminSidebarOverlay" class="hidden fixed inset-0 bg-black/40 z-40 sm:hidden" onclick="closeAdminSidebar()"></div>
<div id="adminSidebar"
     class="admin-sidebar-wrap fixed inset-y-0 left-0 z-50 flex flex-col min-h-screen transform
            w-72 max-w-[85vw] -translate-x-full transition-transform duration-200 ease-out
            sm:static sm:z-auto sm:translate-x-0 sm:w-56 sm:max-w-none sm:flex-shrink-0">
<aside class="admin-sidebar flex-1 flex flex-col min-h-0 w-full">

    {{-- adminLogoPath, qrMenuUrl, qrFilename: Livewire NavigationMenu::mount --}}

    {{-- Logo / Marca --}}
    <div class="admin-sidebar-header px-5 py-6 flex items-start justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0" onclick="closeAdminSidebar()">
            @if(filled($adminLogoPath ?? null))
                <img src="{{ asset('storage/'.$adminLogoPath) }}" alt="Logo"
                     class="w-9 h-9 rounded-lg object-cover flex-shrink-0 border border-black/5">
            @else
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                     style="background: linear-gradient(135deg,#64748b,#334155)">BJ</div>
            @endif
            <div>
                <p class="admin-brand-title text-sm font-semibold text-gray-800 leading-tight">{{ config('app.name') }}</p>
                <p class="admin-brand-sub text-xs text-gray-400">{{ __('admin.nav.subtitle') }}</p>
            </div>
        </a>
        <button type="button"
                class="sm:hidden inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm"
                onclick="closeAdminSidebar()"
                aria-label="Cerrar menú">
            <span aria-hidden="true" class="text-lg leading-none">✕</span>
        </button>
    </div>

    {{-- Botón plegar/desplegar sidebar (solo desktop) --}}
    <div class="hidden sm:flex justify-end px-3 pb-1">
        <button type="button"
                id="sidebarCollapseBtn"
                onclick="toggleAdminSidebarCollapsed()"
                aria-label="Plegar menú"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg id="sidebarCollapseBtnIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    {{-- Navegación: Productos → Categorías → Alérgenos → Maridaje → Avisos → Ver carta → QRs --}}
    {{-- pb-44: hueco para el banner fijo (más alto tras subir tamaños de fuente) --}}
    <nav class="flex-1 px-3 pt-5 pb-44 space-y-0.5" onclick="if(window.innerWidth < 640) { closeAdminSidebar(); }">

        <a href="{{ url('/product') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->is('product*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.products') }}</span>
        </a>

        <a href="{{ url('/category') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->is('category*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.categories') }}</span>
        </a>

        <a href="{{ url('/allergen') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->is('allergen*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.allergens') }}</span>
        </a>

        <a href="{{ url('/pairing') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->is('pairing*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.pairing') }}</span>
        </a>

        <a href="{{ url('/advice') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->is('advice*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.advice') }}</span>
        </a>

        {{-- URL pública: en demo/staging forzamos ?restaurant=ID del selector del admin --}}
        @php
            $rid = session('admin_restaurant_id');
            $fallbackMenuUrl = url('/');
            if ($rid) {
                $fallbackMenuUrl .= (str_contains($fallbackMenuUrl, '?') ? '&' : '?') . http_build_query(['restaurant' => $rid]);
            }
            $menuHref = ($qrMenuUrl ?? null) ?: $fallbackMenuUrl;
        @endphp
        <a href="{{ $menuHref }}" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.view_menu') }}</span>
        </a>

        <button type="button" onclick="toggleSidebarQR()"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <span class="admin-nav-label">{{ __('admin.nav.qrs') }}</span>
        </button>

        <div id="qr-panel" class="hidden px-1 pb-2">
            <div class="flex justify-center rounded-xl border border-gray-100 mt-1 p-2 bg-white">
                <div id="sidebar-qr"></div>
            </div>
            <button type="button" onclick="downloadSidebarQR()"
               class="mt-2 block w-full text-xs text-center text-gray-400 hover:text-gray-700 transition-colors py-1">
                {{ __('admin.actions.download_qr') }}
            </button>
        </div>
        <script>
            @php
                // Fallback: si por lo que sea qrMenuUrl viene vacío, en admin siempre queremos ?restaurant=ID.
                $rid = session('admin_restaurant_id');
                $fallbackMenuUrl = url('/');
                if ($rid) {
                    $fallbackMenuUrl .= (str_contains($fallbackMenuUrl, '?') ? '&' : '?') . http_build_query(['restaurant' => $rid]);
                }
            @endphp
            window.MENU_URL     = @json(($qrMenuUrl ?? null) ?: $fallbackMenuUrl);
            window.QR_FILENAME  = @json($qrFilename ?? 'qr-carta.png');
        </script>

        {{-- Ajustes (última posición) --}}
        <details class="group" @if(request()->is('settings*') || request()->is('translations*')) open @endif>
            <summary class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer select-none
                           {{ request()->is('settings*') || request()->is('translations*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="flex items-center gap-3 min-w-0">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5A3.5 3.5 0 1112 8.5a3.5 3.5 0 010 7z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09A1.65 1.65 0 0015 4.6a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    <span class="truncate admin-nav-label">{{ __('admin.nav.settings') }}</span>
                </span>
                <svg class="w-4 h-4 flex-shrink-0 opacity-70 transition-transform group-open:rotate-180 admin-nav-label" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="mt-1 pl-9 pr-2 space-y-1">
                <a href="{{ url('/settings/appearance') }}"
                   class="block px-3 py-2 rounded-xl text-sm transition-colors
                          {{ request()->is('settings/appearance') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('admin.nav.appearance') }}
                </a>
                <a href="{{ url('/settings/import-products') }}"
                   class="block px-3 py-2 rounded-xl text-sm transition-colors
                          {{ request()->is('settings/import-products') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('admin.nav.import_products') }}
                </a>
                <a href="{{ url('/settings/import-ai') }}"
                   class="block px-3 py-2 rounded-xl text-sm transition-colors
                          {{ request()->is('settings/import-ai') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('admin.nav.import_ai') }}
                </a>
                <a href="{{ url('/translations') }}"
                   class="block px-3 py-2 rounded-xl text-sm transition-colors
                          {{ request()->is('translations*') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('admin.nav.translations') }}
                </a>
                <a href="{{ url('/settings/ai-billing') }}"
                   class="block px-3 py-2 rounded-xl text-sm transition-colors
                          {{ request()->is('settings/ai-billing') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('admin.nav.ai_billing') }}
                </a>
                <a href="{{ url('/settings/orders') }}"
                   class="block px-3 py-2 rounded-xl text-sm transition-colors
                          {{ request()->is('settings/orders') ? 'admin-nav-active' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('admin.nav.orders') }}
                </a>
            </div>
        </details>

    </nav>

</aside>

    {{-- Fuera de <aside>: evita que el flex lo coloque arriba; el banner usa position:fixed al pie del viewport --}}
    <livewire:admin.ai-credits-banner />

</div>

{{-- ── Modal eliminar restaurante (fuera del sidebar para evitar stacking context) ── --}}
<div id="del-rest-modal"
     style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;"
     onclick="if(event.target===this)closeDelModal()">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:24px;max-width:448px;width:calc(100% - 32px);">
        <h3 style="font-size:18px;font-weight:600;color:#1f2937;margin-bottom:8px;">{{ __('admin.restaurant_modal.title') }}</h3>
        <p style="font-size:14px;color:#4b5563;margin-bottom:16px;">
            {{ __('admin.restaurant_modal.body_line1') }} <strong style="color:#ef4444;">{{ __('admin.restaurant_modal.body_warning') }}</strong>
        </p>
        <p style="font-size:13px;color:#6b7280;margin-bottom:6px;">
            {{ __('admin.restaurant_modal.confirm_hint') }} <strong id="del-rest-name-hint" style="color:#111;font-family:monospace;background:#f3f4f6;padding:1px 5px;border-radius:4px;"></strong>
        </p>
        <input id="del-rest-input" type="text" autocomplete="off"
               oninput="checkDelInput()"
               placeholder="{{ __('admin.restaurant_modal.placeholder') }}"
               style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;margin-bottom:20px;box-sizing:border-box;outline:none;">
        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <button onclick="closeDelModal()"
                    style="padding:8px 16px;border-radius:8px;border:1px solid #d1d5db;font-size:14px;font-weight:500;color:#374151;background:#fff;cursor:pointer;">
                {{ __('admin.actions.cancel') }}
            </button>
            <button id="del-rest-confirm-btn" onclick="confirmDelRestaurant()" disabled
                    style="padding:8px 16px;border-radius:8px;border:none;font-size:14px;font-weight:600;color:#fff;background:#fca5a5;cursor:not-allowed;">
                {{ __('admin.actions.delete') }}
            </button>
        </div>
    </div>
</div>

<script>
var _delRestId   = null;
var _delRestName = null;

window.addEventListener('show-delete-restaurant', function(e) {
    _delRestId   = e.detail.id;
    _delRestName = e.detail.name;
    document.getElementById('del-rest-name-hint').textContent = _delRestName;
    document.getElementById('del-rest-input').value = '';
    var btn = document.getElementById('del-rest-confirm-btn');
    btn.disabled = true;
    btn.style.background = '#fca5a5';
    btn.style.cursor = 'not-allowed';
    document.getElementById('del-rest-modal').style.display = 'flex';
    setTimeout(function() { document.getElementById('del-rest-input').focus(); }, 50);
});

function checkDelInput() {
    var val = document.getElementById('del-rest-input').value;
    var btn = document.getElementById('del-rest-confirm-btn');
    var match = (val === _delRestName);
    btn.disabled = !match;
    btn.style.background  = match ? '#ef4444' : '#fca5a5';
    btn.style.cursor      = match ? 'pointer'  : 'not-allowed';
}

function closeDelModal() {
    document.getElementById('del-rest-modal').style.display = 'none';
    _delRestId   = null;
    _delRestName = null;
}

function confirmDelRestaurant() {
    if (!_delRestId) return;
    var val = document.getElementById('del-rest-input').value;
    if (val !== _delRestName) return;
    window.livewire.emit('confirmDeleteRestaurant', _delRestId);
    closeDelModal();
}
</script>
</div>
