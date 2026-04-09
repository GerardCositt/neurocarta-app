<div class="space-y-6">

    {{-- Flash --}}
    @if($flashMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="rounded-xl px-4 py-3 text-sm font-medium {{ $flashType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' }}">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- ── CABECERA ─────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900">Traducciones</h2>
                <p class="text-sm text-gray-500 mt-0.5">Gestiona los textos de la carta en múltiples idiomas con DeepL.</p>
            </div>

            {{-- Selector de idioma destino --}}
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Idioma destino</label>
                <select wire:model="targetLocale"
                        class="rounded-xl border border-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-amber-300 focus:border-amber-400 bg-white">
                    @foreach($languages as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Uso mensual DeepL ──────────────────────── --}}
        <div class="mt-5 p-4 rounded-xl bg-gray-50 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Uso mensual DeepL</span>
                <span class="text-xs text-gray-500">
                    {{ number_format($used) }} / 499.999 caracteres
                    &nbsp;·&nbsp;
                    <span class="{{ $usagePercent >= 90 ? 'text-red-600 font-semibold' : ($usagePercent >= 70 ? 'text-amber-600' : 'text-green-600') }}">
                        {{ $usagePercent }}%
                    </span>
                </span>
            </div>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-2 rounded-full transition-all {{ $usagePercent >= 90 ? 'bg-red-500' : ($usagePercent >= 70 ? 'bg-amber-400' : 'bg-green-500') }}"
                     style="width: {{ min($usagePercent, 100) }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Restantes: {{ number_format($remaining) }} caracteres este mes.</p>
        </div>

        {{-- ── Acciones principales ──────────────────── --}}
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" wire:click="translateAll" wire:loading.attr="disabled"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-amber-500 hover:bg-amber-600 text-white transition-colors disabled:opacity-60 flex items-center gap-2">
                <span wire:loading.remove wire:target="translateAll">⚡ Autotraducir todo (solo nuevos)</span>
                <span wire:loading wire:target="translateAll">Traduciendo…</span>
            </button>

            <button type="button" wire:click="retranslateAll" wire:loading.attr="disabled"
                    wire:confirm="{{ __('admin.translation_ui.confirm_retranslate') }}"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-sky-500 hover:bg-sky-600 text-white transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="retranslateAll">🔄 Retraducir todo</span>
                <span wire:loading wire:target="retranslateAll">Traduciendo…</span>
            </button>

            <button type="button" wire:click="clearLocale"
                    wire:confirm="{{ __('admin.translation_ui.confirm_clear_locale') }}"
                    class="px-4 py-2 rounded-xl text-sm font-semibold border border-red-200 text-red-600 hover:bg-red-50 transition-colors">
                🗑 Borrar idioma
            </button>

            <a href="{{ route('settings.ai-billing') }}"
                    class="ml-auto px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors">
                🔑 Abrir APIs
            </a>
        </div>

        <div class="mt-4 p-4 rounded-xl border border-gray-200 bg-gray-50">
            <p class="text-sm text-gray-600">
                La API key de traducción ahora se configura desde
                <a href="{{ route('settings.ai-billing') }}" class="text-amber-600 underline font-medium">Ajustes → IA y facturación</a>.
            </p>
        </div>
    </div>

    {{-- ── RESUMEN DE IDIOMAS TRADUCIDOS ─────────────────── --}}
    @if($translationSummary->count())
    @php
        $flagMap = ['es'=>'🇪🇸','en'=>'🇬🇧','fr'=>'🇫🇷','de'=>'🇩🇪','it'=>'🇮🇹',
                    'pt'=>'🇵🇹','nl'=>'🇳🇱','pl'=>'🇵🇱','ru'=>'🇷🇺','zh'=>'🇨🇳',
                    'ja'=>'🇯🇵','ar'=>'🇸🇦','cs'=>'🇨🇿','da'=>'🇩🇰','fi'=>'🇫🇮',
                    'el'=>'🇬🇷','hu'=>'🇭🇺','ro'=>'🇷🇴','sk'=>'🇸🇰','sv'=>'🇸🇪',
                    'tr'=>'🇹🇷','uk'=>'🇺🇦','bg'=>'🇧🇬','et'=>'🇪🇪','lt'=>'🇱🇹',
                    'lv'=>'🇱🇻','sl'=>'🇸🇮','id'=>'🇮🇩','nb'=>'🇳🇴','pt_BR'=>'🇧🇷'];
        $langNames = \App\Services\DeepLService::SUPPORTED_LANGUAGES;
        $summaryLabels = [];
        foreach($translationSummary as $loc => $stats) {
            $flag  = $flagMap[$loc] ?? '🌐';
            $label = strtoupper($loc);
            foreach($langNames as $dlCode => $dlLabel) {
                if (\App\Services\DeepLService::deepLToLocale($dlCode) === $loc) { $label = $dlLabel; break; }
            }
            $summaryLabels[$loc] = ['flag' => $flag, 'label' => $label, 'stats' => $stats, 'active' => ($loc === $locale)];
        }
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <details class="group">
            <summary class="flex items-center justify-between px-6 py-4 cursor-pointer select-none hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700">Idiomas con traducciones</span>
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $translationSummary->count() }}</span>
                    {{-- Miniaturas de banderas siempre visibles --}}
                    <div class="flex gap-1">
                        @foreach($summaryLabels as $loc => $info)
                            <span title="{{ $info['label'] }}"
                                  class="text-lg leading-none {{ $info['active'] ? 'ring-2 ring-amber-400 rounded-full' : '' }}">{{ $info['flag'] }}</span>
                        @endforeach
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="px-6 pb-5 pt-1 flex flex-wrap gap-3">
                @foreach($summaryLabels as $loc => $info)
                    <div class="flex items-center gap-2 px-4 py-3 rounded-xl border {{ $info['active'] ? 'border-amber-300 bg-amber-50' : 'border-gray-100 bg-gray-50' }}">
                        <span class="text-2xl leading-none">{{ $info['flag'] }}</span>
                        <div>
                            <p class="text-sm font-semibold {{ $info['active'] ? 'text-amber-700' : 'text-gray-800' }}">{{ $info['label'] }}</p>
                            <p class="text-xs text-gray-400">{{ $info['stats']['items'] }} elementos · {{ $info['stats']['fields'] }} campos</p>
                        </div>
                        @if($info['active'])
                            <span class="ml-1 text-xs text-amber-500 font-medium">activo</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    </div>
    @endif

    {{-- ── SECCIÓN HELPER ───────────────────────────────── --}}
    @php
        $sections = [
            ['label' => 'Productos',   'type' => 'product',  'items' => $products,   'fields' => ['name' => 'Nombre', 'description' => 'Descripción']],
            ['label' => 'Categorías',  'type' => 'category', 'items' => $categories, 'fields' => ['name' => 'Nombre']],
            ['label' => 'Alérgenos',   'type' => 'allergen', 'items' => $allergens,  'fields' => ['name' => 'Nombre']],
            ['label' => 'Avisos',      'type' => 'advice',   'items' => $advices,    'fields' => ['title' => 'Título', 'advice' => 'Texto']],
            ['label' => 'Maridajes',   'type' => 'pairing',  'items' => $pairings,   'fields' => ['name' => 'Nombre', 'description' => 'Descripción']],
        ];
    @endphp

    @foreach($sections as $section)
        @if($section['items']->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">{{ $section['label'] }}</h3>
                <span class="text-xs text-gray-400">{{ $section['items']->count() }} registros</span>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($section['items'] as $item)
                    <div class="px-6 py-4">
                        {{-- Modo edición --}}
                        @if($editingModel === $section['type'] && $editingId === $item->id)
                            <div class="space-y-3">
                                @foreach($section['fields'] as $field => $fieldLabel)
                                    <div>
                                        <label class="text-xs font-medium text-gray-500 mb-1 block">{{ $fieldLabel }}</label>
                                        @if($field === 'description' || $field === 'advice')
                                            <textarea wire:model="editingValues.{{ $field }}" rows="3"
                                                      class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-amber-300 resize-none"></textarea>
                                        @else
                                            <input type="text" wire:model="editingValues.{{ $field }}"
                                                   class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-amber-300">
                                        @endif
                                        <p class="text-xs text-gray-400 mt-0.5">Original (ES): {{ $item->getAttribute($field) }}</p>
                                    </div>
                                @endforeach
                                <div class="flex gap-2 pt-1">
                                    <button type="button" wire:click="saveEdit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-500 hover:bg-green-600 text-white transition-colors">
                                        Guardar
                                    </button>
                                    <button type="button" wire:click="cancelEdit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            </div>

                        {{-- Modo visualización --}}
                        @else
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-medium text-gray-800">{{ $item->getAttribute('name') ?? $item->getAttribute('title') }}</span>
                                        @if($item->hasTranslationFor($locale))
                                            <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">✓ traducido</span>
                                        @else
                                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">sin traducción</span>
                                        @endif
                                    </div>

                                    {{-- Mostrar traducción actual si existe --}}
                                    @foreach($section['fields'] as $field => $fieldLabel)
                                        @php $translated = $item->translate($locale, $field); @endphp
                                        @if($translated && $translated !== $item->getAttribute($field))
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                <span class="font-medium">{{ $fieldLabel }}:</span>
                                                {{ Str::limit($translated, 100) }}
                                            </p>
                                        @endif
                                    @endforeach
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button"
                                            wire:click="translateOne('{{ $section['type'] }}', {{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            title="Autotraducir este elemento"
                                            class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors text-xs">
                                        ⚡
                                    </button>
                                    <button type="button"
                                            wire:click="startEdit('{{ $section['type'] }}', {{ $item->id }})"
                                            title="Editar traducción"
                                            class="p-1.5 rounded-lg text-sky-600 hover:bg-sky-50 transition-colors text-xs">
                                        ✏️
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

</div>
