<div class="space-y-6 relative">

    <style>
        @keyframes import-ai-progress-slide {
            0% { left: -40%; }
            100% { left: 100%; }
        }
        .import-ai-progress-track {
            position: relative;
            height: 0.5rem;
            border-radius: 9999px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .import-ai-progress-fill {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 40%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #6b7280, #111827);
            animation: import-ai-progress-slide 1.35s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
    </style>

    {{-- Overlay durante análisis (Livewire no pinta el paso "processing" en la misma petición larga) --}}
    {{-- display:none inicial: sin esto, el overlay puede verse antes de hidratar Livewire y bloquear la página --}}
    <div wire:loading.flex.delay.shortest wire:target="process"
         style="display: none;"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-white/75 backdrop-blur-[1px] px-4"
         aria-live="polite" aria-busy="true">
        <div class="max-w-md w-full rounded-2xl border border-gray-200 bg-white shadow-lg p-6 text-center">
            <p class="text-4xl mb-3" aria-hidden="true">🤖</p>
            <p class="text-base font-semibold text-gray-800">Analizando la carta con IA…</p>
            <p class="text-sm text-gray-500 mt-2">Leyendo el archivo y extrayendo productos. Puede tardar hasta un minuto.</p>
            <div class="import-ai-progress-track mt-5" role="progressbar" aria-valuetext="Trabajando">
                <div class="import-ai-progress-fill"></div>
            </div>
            <p class="text-xs text-gray-400 mt-3">No cierres esta pestaña.</p>
        </div>
    </div>

    {{-- Overlay al guardar importación (misma petición larga, feedback con barra) --}}
    <div wire:loading.flex.delay.shortest wire:target="save"
         style="display: none;"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-white/75 backdrop-blur-[1px] px-4"
         aria-live="polite" aria-busy="true">
        <div class="max-w-md w-full rounded-2xl border border-gray-200 bg-white shadow-lg p-6 text-center">
            <p class="text-4xl mb-3" aria-hidden="true">💾</p>
            <p class="text-base font-semibold text-gray-800">Guardando productos…</p>
            <p class="text-sm text-gray-500 mt-2">Escribiendo en la base de datos. Espera un momento.</p>
            <div class="import-ai-progress-track mt-5" role="progressbar" aria-valuetext="Guardando">
                <div class="import-ai-progress-fill"></div>
            </div>
        </div>
    </div>

    {{-- Subida de archivo en curso --}}
    <div wire:loading.block.delay wire:target="file"
         style="display: none;"
         class="rounded-xl px-4 py-3 text-sm font-medium bg-gray-100 text-gray-700 border border-gray-200">
        <p>Subiendo archivo…</p>
        <div class="import-ai-progress-track mt-3" role="progressbar" aria-valuetext="Subiendo">
            <div class="import-ai-progress-fill"></div>
        </div>
    </div>

    {{-- Flash --}}
    @if($flashMessage)
        <div class="rounded-xl px-4 py-3 text-sm font-medium {{ $flashType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' }}">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- ── CABECERA ────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0 text-xl">🤖</div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900">Importar carta con IA</h2>
                <p class="text-sm text-gray-500 mt-0.5">Sube una foto o PDF de tu carta y la IA extraerá los productos, categorías y precios automáticamente.</p>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'border-sky-200 bg-sky-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3">
            <p class="text-sm font-semibold {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'text-sky-900' : 'text-amber-900' }}">
                Saldo IA: {{ $aiCredits['label'] }}
            </p>
            <p class="mt-1 text-xs {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'text-sky-700' : 'text-amber-700' }}">
                @if($aiCredits['uses_client_key'])
                    Esta importación usa la API key del cliente. No se descuentan créditos de la plataforma.
                @elseif($aiCredits['is_demo_unlimited'])
                    Modo demo activo. Puedes probar la importación y la generación de imágenes sin descuento de créditos.
                @else
                    Importar carta: {{ $importCost }} créditos. Imagen extra por producto importado: {{ $imageCost }} créditos.
                @endif
            </p>
        </div>

        {{-- Pasos visuales --}}
        <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
            <span class="flex items-center gap-1.5 {{ in_array($step, ['upload','preview','saving','done']) ? 'text-gray-800 font-semibold' : '' }}">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold {{ in_array($step, ['upload','preview','saving','done']) ? 'bg-gray-700' : 'bg-gray-200' }}">1</span>
                Subir
            </span>
            <span class="flex-1 h-px bg-gray-200"></span>
            <span class="flex items-center gap-1.5 {{ in_array($step, ['preview','saving','done']) ? 'text-gray-800 font-semibold' : '' }}">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold {{ in_array($step, ['preview','saving','done']) ? 'bg-gray-700' : 'bg-gray-200' }}">2</span>
                Revisar
            </span>
            <span class="flex-1 h-px bg-gray-200"></span>
            <span class="flex items-center gap-1.5 {{ in_array($step, ['done']) ? 'text-gray-800 font-semibold' : '' }}">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold {{ in_array($step, ['done']) ? 'bg-gray-700' : 'bg-gray-200' }}">3</span>
                Importar
            </span>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-800">Configuración de OpenAI</p>
                @if($configured)
                    <p class="text-xs text-green-600 mt-1">✓ Configurada y lista</p>
                @else
                    <p class="text-xs text-red-500 mt-1">No configurada — necesaria para analizar cartas</p>
                @endif
                <p class="text-xs text-gray-500 mt-2">La API key ahora se gestiona desde Ajustes → IA y facturación.</p>
            </div>
            <a href="{{ route('settings.ai-billing') }}"
               class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                Abrir IA y facturación
            </a>
        </div>
    </div>

    {{-- ── PASO 1: SUBIDA ──────────────────────────────── --}}
    @if($step === 'upload')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <label for="import-ai-file" class="block cursor-pointer">
            <div class="border-2 border-dashed border-gray-200 hover:border-gray-400 rounded-2xl p-10 text-center transition-colors">
                <div class="space-y-3 pointer-events-none">
                    <div class="text-5xl">📄</div>
                    <p class="text-sm font-medium text-gray-700">Haz clic para elegir archivo o usa el botón inferior</p>
                    <p class="text-xs text-gray-400">JPG, PNG o PDF · máximo 10 MB</p>
                </div>
            </div>
            <input id="import-ai-file" type="file" wire:model="file" accept=".jpg,.jpeg,.png,.pdf"
                   class="sr-only">
        </label>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <label for="import-ai-file"
                   class="inline-flex px-4 py-2 rounded-xl text-sm font-semibold bg-gray-800 hover:bg-gray-900 text-white cursor-pointer transition-colors">
                Elegir archivo…
            </label>
            @if(!$file)
                <span class="text-xs text-gray-500">Ningún archivo seleccionado</span>
            @endif
        </div>

        @if($file)
            <div class="mt-4 flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-2xl">{{ str_ends_with(strtolower($file->getClientOriginalName()), '.pdf') ? '📋' : '🖼️' }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $file->getClientOriginalName() }}</p>
                    <p class="text-xs text-gray-400">{{ round($file->getSize() / 1024) }} KB</p>
                </div>
                <button type="button" wire:click="$set('file', null)" class="text-gray-400 hover:text-red-500 transition-colors" title="Quitar archivo">✕</button>
            </div>
        @endif

        @error('file')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        @if(!$configured)
            <p class="mt-4 text-sm text-gray-800 bg-gray-100 border border-gray-200 rounded-xl px-4 py-3">
                Configura primero la <strong>API key de OpenAI</strong> en el bloque de arriba para habilitar el análisis.
            </p>
        @endif

        <label class="mt-4 flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 cursor-pointer">
            <input type="checkbox" wire:model="generateImages"
                   class="mt-0.5 w-4 h-4 rounded text-gray-700 focus:ring-gray-400">
            <span class="text-sm text-gray-700">
                Generar también una imagen de plato con IA para cada producto importado que se cree.
                <span class="block text-xs text-gray-400 mt-1">
                    Coste adicional: {{ $imageCost }} créditos por producto al que se le genere imagen.
                    @if($aiCredits['uses_client_key'])
                        Al usar la API key del cliente, esta opción no descuenta créditos de la plataforma.
                    @elseif($aiCredits['is_demo_unlimited'])
                        En esta demo no se descuenta saldo.
                    @else
                        Útil para cartas sin fotos, pero puede tardar más y consumir más API.
                    @endif
                </span>
            </span>
        </label>

        <div class="mt-5 flex flex-col items-center gap-3">
            @if($file)
                <button type="button" wire:click="restart"
                        class="px-4 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
            @else
                <span></span>
            @endif
            <button type="button" wire:click="process" wire:loading.attr="disabled" wire:target="process,file"
                    @if(!$file || !$configured) disabled @endif
                    style="padding-left: 3rem; padding-right: 3rem; min-width: 26rem;"
                    class="py-4 rounded-2xl text-base font-bold bg-green-600 hover:bg-green-700 border-2 border-green-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2 w-auto max-w-full whitespace-nowrap">
                <span wire:loading.remove wire:target="process">✨ Analizar con IA</span>
                <span wire:loading wire:target="process">Analizando…</span>
            </button>
        </div>
    </div>
    @endif

    {{-- ── PASO 3: PREVISUALIZACIÓN EDITABLE ──────────── --}}
    @if($step === 'preview')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Resultado extraído</h3>
                <p class="text-xs text-gray-400 mt-0.5">Revisa y corrige antes de importar. Desmarca los que no quieras.</p>
            </div>
            <span class="text-sm font-medium text-gray-800 bg-gray-100 px-3 py-1 rounded-full">
                {{ $totalSelected }} productos seleccionados
            </span>
        </div>

        <div class="space-y-4">
            @foreach($extracted as $ci => $cat)
            <div class="border border-gray-100 rounded-xl overflow-hidden" wire:key="import-ai-cat-{{ $ci }}">
                {{-- Cabecera categoría --}}
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <input type="checkbox"
                           wire:click="toggleCategory({{ $ci }})"
                           @if(collect($selected[$ci] ?? [])->contains(true)) checked @endif
                           class="w-4 h-4 rounded text-gray-700 focus:ring-gray-400">
                    <input type="text"
                           wire:model="extracted.{{ $ci }}.name"
                           class="flex-1 text-sm font-semibold text-gray-800 bg-transparent border-0 focus:ring-0 p-0 focus:outline-none">
                    <span class="text-xs text-gray-400">{{ count($cat['products'] ?? []) }} productos</span>
                </div>

                {{-- Productos --}}
                <div class="divide-y divide-gray-50">
                    @foreach($cat['products'] ?? [] as $pi => $prod)
                    <div class="flex items-start gap-3 px-4 py-3 {{ empty($selected[$ci][$pi]) ? 'opacity-40' : '' }}"
                         wire:key="import-ai-cat-{{ $ci }}-prod-{{ $pi }}">
                        <input type="checkbox"
                               wire:click="toggleProduct({{ $ci }}, {{ $pi }})"
                               @if(!empty($selected[$ci][$pi])) checked @endif
                               class="mt-1 w-4 h-4 rounded text-gray-700 focus:ring-gray-400 flex-shrink-0">
                        <div class="flex-1 min-w-0 flex flex-col gap-2">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <input type="text"
                                       wire:model="extracted.{{ $ci }}.products.{{ $pi }}.name"
                                       placeholder="Nombre"
                                       class="text-sm font-medium text-gray-800 border border-gray-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <input type="text"
                                       wire:model="extracted.{{ $ci }}.products.{{ $pi }}.description"
                                       placeholder="Descripción (opcional)"
                                       class="text-sm text-gray-500 border border-gray-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <input type="number" step="0.01" min="0"
                                       wire:model="extracted.{{ $ci }}.products.{{ $pi }}.price"
                                       placeholder="Precio €"
                                       class="text-sm text-gray-800 border border-gray-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 w-28">
                            </div>
                            @if(!empty($prod['allergens']) && is_array($prod['allergens']))
                            <div class="flex flex-wrap gap-1">
                                @foreach($prod['allergens'] as $al)
                                    <span class="text-xs bg-gray-100 text-gray-700 border border-gray-200 rounded-full px-2 py-0.5">{{ is_scalar($al) ? $al : '' }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <button type="button" wire:click="restart"
                    class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                ← Volver a subir
            </button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    @if($totalSelected === 0) disabled @endif
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-green-600 hover:bg-green-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 w-full sm:w-auto">
                <span wire:loading.remove wire:target="save">✅ Importar {{ $totalSelected }} productos</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>
    </div>
    @endif

    {{-- ── PASO 4: GUARDANDO ───────────────────────────── --}}
    @if($step === 'saving')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center max-w-lg mx-auto">
        <div class="text-5xl mb-4">💾</div>
        <p class="text-base font-semibold text-gray-700">Guardando en la base de datos…</p>
        <div class="import-ai-progress-track mt-6 max-w-sm mx-auto" role="progressbar" aria-valuetext="Guardando">
            <div class="import-ai-progress-fill"></div>
        </div>
    </div>
    @endif

    {{-- ── PASO 5: HECHO ───────────────────────────────── --}}
    @if($step === 'done')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="text-5xl mb-4">🎉</div>
        <h3 class="text-lg font-bold text-gray-800">¡Importación completada!</h3>
        <p class="text-sm text-gray-500 mt-1">
            Se han creado <strong>{{ $savedProducts }} productos</strong>
            en <strong>{{ $savedCategories }} categorías nuevas</strong>.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
            <a href="{{ url('/product') }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gray-800 hover:bg-gray-900 text-white transition-colors text-center">
                Ver productos
            </a>
            <button type="button" wire:click="restart"
                    class="px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                Importar otra carta
            </button>
        </div>
    </div>
    @endif

</div>
