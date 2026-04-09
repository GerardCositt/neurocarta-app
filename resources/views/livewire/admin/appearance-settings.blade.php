<div>
    <style>
        .admin-accent-color-native {
            -webkit-appearance: none;
            appearance: none;
            width: 3rem;
            height: 3rem;
            padding: 0;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            background: transparent;
        }
        .admin-accent-color-native::-webkit-color-swatch-wrapper { padding: 0; }
        .admin-accent-color-native::-webkit-color-swatch {
            border: none;
            border-radius: 0.375rem;
        }
        .admin-accent-color-native::-moz-color-swatch {
            border: none;
            border-radius: 0.375rem;
        }
    </style>
    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800">Apariencia</h3>
            <p class="text-sm text-gray-500 mt-1">Tema del panel (claro, oscuro o según el sistema).</p>

            <div class="mt-4">
                <div class="flex rounded-xl border border-gray-200 overflow-hidden shadow-sm admin-theme-switch"
                     role="group" aria-label="Apariencia del panel">
                    <button type="button"
                            class="admin-theme-btn flex-1 px-3 py-2.5 text-sm border-0 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-400/50 transition-colors"
                            data-admin-theme="light" aria-pressed="false"
                            onclick="window.applyAdminTheme && window.applyAdminTheme('light', true)">Claro</button>
                    <button type="button"
                            class="admin-theme-btn flex-1 px-3 py-2.5 text-sm border-0 border-l border-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-400/50 transition-colors"
                            data-admin-theme="dark" aria-pressed="false"
                            onclick="window.applyAdminTheme && window.applyAdminTheme('dark', true)">Oscuro</button>
                    <button type="button"
                            class="admin-theme-btn flex-1 px-3 py-2.5 text-sm border-0 border-l border-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-400/50 transition-colors"
                            data-admin-theme="system" aria-pressed="false"
                            onclick="window.applyAdminTheme && window.applyAdminTheme('system', true)">Sistema</button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800">Logo del local</h3>
            <p class="text-sm text-gray-500 mt-1">Se mostrará junto al nombre del local en el panel.</p>

            <div class="mt-4 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl border border-gray-200 bg-white overflow-hidden flex items-center justify-center">
                    @if($logoFile)
                        <img src="{{ $logoFile->temporaryUrl() }}" alt="Logo" class="w-full h-full object-cover">
                    @elseif($currentLogoPath)
                        <img src="{{ asset('storage/'.$currentLogoPath) }}" alt="Logo" class="w-full h-full object-cover">
                    @else
                        <span class="text-xs font-bold text-gray-400">BJ</span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <input type="file" wire:model="logoFile" accept="image/*"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100">

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="saveLogo" wire:loading.attr="disabled"
                                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-green-500 hover:bg-green-600 text-white transition-colors disabled:opacity-60">
                                Guardar
                            </button>
                            @if($currentLogoPath)
                                <button type="button" wire:click="removeLogo" wire:loading.attr="disabled"
                                        class="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 transition-colors disabled:opacity-60">
                                    Quitar
                                </button>
                            @endif
                        </div>
                    </div>

                    @error('logoFile')
                        <x-admin.banner variant="danger" :show-icon="false" class="mt-2 mb-0 py-2">{{ $message }}</x-admin.banner>
                    @enderror

                    <p class="text-xs text-gray-400 mt-2">{{ __('admin.appearance.logo_formats') }}</p>
                </div>
            </div>
        </div>
    </div>

    @php
        $_logoLower = $currentLogoPath ? strtolower($currentLogoPath) : '';
        $isSvgLogo = $currentLogoPath && substr($_logoLower, -4) === '.svg';
        $paletteOrder = [
            '--gold' => __('admin.appearance.palette_var_gold'),
            '--gold-light' => __('admin.appearance.palette_var_gold_light'),
            '--gold-dim' => __('admin.appearance.palette_var_gold_dim'),
            '--red' => __('admin.appearance.palette_var_red'),
            '--bg' => __('admin.appearance.palette_var_bg'),
            '--surface' => __('admin.appearance.palette_var_surface'),
            '--surface-el' => __('admin.appearance.palette_var_surface_el'),
            '--text' => __('admin.appearance.palette_var_text'),
            '--text-muted' => __('admin.appearance.palette_var_text_muted'),
        ];
    @endphp

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800">{{ __('admin.appearance.palette_title') }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ __('admin.appearance.palette_intro') }}</p>

        @if($menuPalette)
            @if(!empty($menuPalette['accent_hex']))
                <div class="inline-flex items-center gap-3 mt-4 text-xs font-medium text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 max-w-full">
                    <span class="w-10 h-10 rounded-lg border border-amber-400 shadow-inner flex-shrink-0"
                          style="background-color: {{ $menuPalette['accent_hex'] }};"
                          role="img"
                          aria-label="{{ __('admin.appearance.palette_accent_swatch_aria', ['hex' => $menuPalette['accent_hex']]) }}"></span>
                    <span class="min-w-0">{{ __('admin.appearance.palette_accent_detected', ['hex' => $menuPalette['accent_hex']]) }}</span>
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">{{ __('admin.appearance.palette_dark_preview') }}</p>
                    <div class="flex flex-wrap gap-4">
                        @foreach($paletteOrder as $var => $label)
                            @if(!empty($menuPalette['vars_dark'][$var]))
                                <div class="flex flex-col items-center">
                                    {{-- Tailwind v2 no incluye aspect-square; h-16 evita cuadros con altura 0 --}}
                                    <div class="w-16 h-16 rounded-xl border border-gray-300 shadow-inner flex-shrink-0"
                                         style="background-color: {{ $menuPalette['vars_dark'][$var] }};"
                                         title="{{ $label }} — {{ $menuPalette['vars_dark'][$var] }}"
                                         aria-label="{{ $label }}, {{ $menuPalette['vars_dark'][$var] }}"></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">{{ __('admin.appearance.palette_light_preview') }}</p>
                    <div class="flex flex-wrap gap-4">
                        @foreach($paletteOrder as $var => $label)
                            @if(!empty($menuPalette['vars_light'][$var]))
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-xl border border-gray-300 shadow-inner flex-shrink-0"
                                         style="background-color: {{ $menuPalette['vars_light'][$var] }};"
                                         title="{{ $label }} — {{ $menuPalette['vars_light'][$var] }}"
                                         aria-label="{{ $label }}, {{ $menuPalette['vars_light'][$var] }}"></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-6 border-t border-gray-100 pt-4">{{ __('admin.appearance.palette_footer') }}</p>
        @elseif($isSvgLogo)
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 mt-4">{{ __('admin.appearance.palette_svg_notice') }}</p>
        @elseif($currentLogoPath)
            <p class="text-sm text-gray-600 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 mt-4">{{ __('admin.appearance.palette_extract_failed') }}</p>
        @else
            <p class="text-sm text-gray-500 mt-4">{{ __('admin.appearance.palette_no_logo') }}</p>
        @endif

        <div class="mt-6 pt-6 border-t border-gray-100">
            <h4 class="text-sm font-semibold text-gray-800">{{ __('admin.appearance.accent_manual_title') }}</h4>
            <p class="text-xs text-gray-500 mt-1 mb-3">{{ __('admin.appearance.accent_manual_intro') }}</p>

            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                {{ $accentPresetsFromLogo ? __('admin.appearance.accent_logo_swatches_label') : __('admin.appearance.accent_fallback_swatches_label') }}
            </p>
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($accentPresets as $presetHex)
                    <button type="button"
                            wire:click="applyAccentSwatch('{{ $presetHex }}')"
                            wire:loading.attr="disabled"
                            class="w-10 h-10 rounded-lg border-2 border-gray-200 shadow-sm flex-shrink-0 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:opacity-50"
                            style="background-color: {{ $presetHex }};"
                            title="{{ $presetHex }}"
                            aria-label="{{ __('admin.appearance.accent_preset_pick', ['hex' => $presetHex]) }}"></button>
                @endforeach
            </div>

            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.appearance.accent_system_picker_label') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <input type="color"
                       id="admin-accent-color-native"
                       wire:model="accentHexInput"
                       wire:change="saveAccentColor"
                       class="admin-accent-color-native flex-shrink-0"
                       aria-label="{{ __('admin.appearance.accent_color_picker') }}"
                       title="{{ __('admin.appearance.accent_color_picker') }}">
                <button type="button"
                        class="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-300 bg-white hover:bg-gray-50 text-gray-800 transition-colors"
                        onclick="document.getElementById('admin-accent-color-native').click(); return false;">
                    {{ __('admin.appearance.accent_open_system_picker') }}
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ __('admin.appearance.accent_hex_optional_hint') }}</p>
            @error('accentHexInput')
                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
