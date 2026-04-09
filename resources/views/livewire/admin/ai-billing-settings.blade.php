<div>
    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 scroll-mt-24 max-w-5xl">
        <h3 class="text-lg font-semibold text-gray-800">IA y facturación</h3>
        <p class="text-xs text-gray-500 mt-1">Decide si este restaurante usa nuestras APIs o las suyas.</p>

        @php
            $hasOpenAiKey = filled($openAiApiKey);
            $hasDeepLKey = filled($deepLApiKey);
        @endphp

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <div class="admin-bulk-tooltip admin-bulk-tooltip--below">
                <button type="button"
                        class="admin-bulk-tooltip__trigger"
                        aria-describedby="ai-system-tooltip"
                        aria-label="Información sobre IA del sistema">
                    <svg class="admin-bulk-tooltip__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    IA del sistema
                </button>
                <div id="ai-system-tooltip" class="admin-bulk-tooltip__bubble" role="tooltip">
                    <p class="admin-bulk-tooltip__line">La plataforma pone la API cuando el restaurante no configura sus propias claves.</p>
                    <p class="admin-bulk-tooltip__line admin-bulk-tooltip__line--fine">
                        @if($aiCredits['is_demo_unlimited'])
                            Demo activa: aquí no se cobra aunque este modo exista.
                        @elseif($aiCredits['uses_client_key'])
                            Ahora mismo no está activa porque el restaurante usa sus propias APIs.
                        @else
                            Si este modo está activo, sí puede consumir créditos o facturación de plataforma.
                        @endif
                    </p>
                </div>
            </div>

            <div class="admin-bulk-tooltip admin-bulk-tooltip--below">
                <button type="button"
                        class="admin-bulk-tooltip__trigger"
                        aria-describedby="my-apis-tooltip"
                        aria-label="Información sobre mis APIs">
                    <svg class="admin-bulk-tooltip__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0v4m-9 0h10a2 2 0 012 2v4a2 2 0 01-2 2H7a2 2 0 01-2-2v-4a2 2 0 012-2z"/>
                    </svg>
                    Mis APIs
                </button>
                <div id="my-apis-tooltip" class="admin-bulk-tooltip__bubble" role="tooltip">
                    <p class="admin-bulk-tooltip__line">Si subes tus claves, la plataforma usa las APIs del restaurante y no descuenta créditos de la plataforma.</p>
                    <p class="admin-bulk-tooltip__line admin-bulk-tooltip__line--fine">
                        OpenAI: {{ $hasOpenAiKey ? 'configurada' : 'sin configurar' }} · DeepL: {{ $hasDeepLKey ? 'configurada' : 'sin configurar' }}
                    </p>
                </div>
            </div>

            <button type="button"
                    onclick="document.getElementById('openai-api-input') && document.getElementById('openai-api-input').focus()"
                    class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-800 hover:bg-sky-100 transition-colors">
                Sube la API de tu IA
            </button>

            <div class="admin-bulk-tooltip admin-bulk-tooltip--below">
                <button type="button"
                        wire:click="buyCredits"
                        class="admin-bulk-tooltip__trigger"
                        aria-describedby="buy-credits-tooltip"
                        aria-label="Comprar créditos">
                    <svg class="admin-bulk-tooltip__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-10c1.714 0 3.175.537 3.732 1.286M12 8V6m0 12v-2m0 0c-1.714 0-3.175-.537-3.732-1.286"/>
                    </svg>
                    Comprar créditos
                </button>
                <div id="buy-credits-tooltip" class="admin-bulk-tooltip__bubble" role="tooltip">
                    <p class="admin-bulk-tooltip__line font-semibold text-gray-700">Recargas</p>
                    @foreach($creditPackages as $package)
                        <p class="admin-bulk-tooltip__line flex items-center justify-between gap-3">
                            <span>{{ $package['label'] }}</span>
                            <span class="font-semibold whitespace-nowrap">{{ $package['credits'] }} cr · {{ $package['euros'] }}</span>
                        </p>
                    @endforeach
                    <div class="mt-2 pt-2 border-t border-slate-200 relative"
                         onmouseenter="this.querySelector('[data-consumos-panel]').classList.remove('hidden')"
                         onmouseleave="this.querySelector('[data-consumos-panel]').classList.add('hidden')">
                        <div class="flex items-center justify-between gap-3 text-[11px] font-semibold text-gray-700 cursor-default">
                            <span>Consumos</span>
                            <span class="text-gray-400">></span>
                        </div>
                        <div class="hidden pt-2" data-consumos-panel>
                            @foreach($priceTariff as $item)
                                <p class="admin-bulk-tooltip__line flex items-center justify-between gap-3">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="font-semibold whitespace-nowrap">{{ $item['credits'] }} cr · {{ $item['euros'] }}</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                    <p class="admin-bulk-tooltip__line admin-bulk-tooltip__line--fine">
                        Aquí conectaremos Stripe para comprar estos paquetes.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4 w-full rounded-xl border border-gray-200 bg-white px-4 py-3" style="max-width: 860px;">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-gray-800">Claves API</p>
                <div class="text-xs {{ $aiCredits['uses_client_key'] || $hasOpenAiKey || $hasDeepLKey ? 'text-sky-700' : 'text-amber-700' }}">
                    {{ $aiCredits['label'] }}
                </div>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5">
                    <div class="mt-2 flex items-center gap-2">
                        <span class="shrink-0 text-sm font-semibold text-gray-800">OpenAI</span>
                        <input id="openai-api-input" type="password" wire:model.defer="openAiApiKey" placeholder="sk-..."
                               class="flex-1 rounded-xl border border-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white min-w-0">
                        <button type="button" wire:click="saveOpenAiApiKey"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-gray-800 hover:bg-gray-900 text-white transition-colors shrink-0">
                            Guardar
                        </button>
                    </div>
                    @error('openAiApiKey') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5">
                    <div class="mt-2 flex items-center gap-2">
                        <span class="shrink-0 text-sm font-semibold text-gray-800">DeepL</span>
                        <input type="password" wire:model.defer="deepLApiKey" placeholder="DeepL-Auth-Key ..."
                               class="flex-1 rounded-xl border border-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white min-w-0">
                        <button type="button" wire:click="saveDeepLApiKey"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-gray-800 hover:bg-gray-900 text-white transition-colors shrink-0">
                            Guardar
                        </button>
                    </div>
                    @error('deepLApiKey') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 bg-white px-4 py-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-base font-semibold text-gray-800">Consumos de IA</p>
                    <p class="mt-1 text-xs text-gray-500">Últimos movimientos de IA de este restaurante.</p>
                </div>
                <div class="text-right">
                    <div class="text-[11px] text-gray-400">{{ $usageLogs->count() }} registros</div>
                    <div class="text-xs font-semibold text-gray-600">{{ $displayCreditsUsed }} créditos usados</div>
                    <div class="text-[11px] text-gray-500">{{ $displayEurosUsed }}</div>
                </div>
            </div>

            @if($usageLogs->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-400 text-center">
                    Aún no hay consumos registrados.
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-[11px] uppercase tracking-wide text-gray-400">
                                <th class="py-2 pr-3 font-semibold">Fecha</th>
                                <th class="py-2 pr-3 font-semibold">Acción</th>
                                <th class="py-2 pr-3 font-semibold">Detalle</th>
                                <th class="py-2 pr-3 font-semibold">Estado</th>
                                <th class="py-2 font-semibold text-right">Créditos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usageLogs as $log)
                                @php
                                    $actionLabel = match($log->action) {
                                        'generate_product_image' => 'Generar imagen',
                                        'improve_product_image' => 'Arreglar imagen',
                                        'bulk_generate_product_images' => 'Generación masiva',
                                        'generate_product_description' => 'Generar descripción',
                                        'generate_product_allergen_text' => 'Texto alérgenos',
                                        'import_menu' => 'Importar carta',
                                        'import_menu_product_image' => 'Imágenes en importación',
                                        default => $log->action,
                                    };

                                    $detail = $log->meta['product_name']
                                        ?? $log->meta['source']
                                        ?? $log->meta['mode']
                                        ?? ($log->product_id ? 'Producto #' . $log->product_id : '—');

                                    $statusLabel = match($log->status) {
                                        'demo' => 'Demo',
                                        'client_key' => 'API cliente',
                                        'completed' => 'Cobrado',
                                        default => ucfirst((string) $log->status),
                                    };
                                @endphp
                                <tr class="border-b border-gray-50 last:border-b-0">
                                    <td class="py-3 pr-3 text-gray-500 whitespace-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 pr-3 text-gray-800 font-medium">{{ $actionLabel }}</td>
                                    <td class="py-3 pr-3 text-gray-500">{{ $detail }}</td>
                                    <td class="py-3 pr-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold
                                            @if($log->status === 'completed') bg-amber-50 text-amber-700
                                            @elseif($log->status === 'client_key') bg-sky-50 text-sky-700
                                            @elseif($log->status === 'demo') bg-emerald-50 text-emerald-700
                                            @else bg-gray-100 text-gray-600 @endif">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right {{ (int) ($log->display_credits ?? 0) > 0 ? 'text-gray-800' : 'text-gray-400' }}">
                                        <div class="font-semibold">
                                            {{ (int) ($log->display_credits ?? 0) > 0 ? $log->display_credits : '0' }}
                                        </div>
                                        <div class="text-[11px] {{ (int) ($log->display_credits ?? 0) > 0 ? 'text-gray-500' : 'text-gray-400' }}">
                                            {{ $log->display_euros ?? '0,00 €' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
