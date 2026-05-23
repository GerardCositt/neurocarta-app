<div>
    @if (session()->has('status'))
        <x-admin.banner variant="success">{{ session('status') }}</x-admin.banner>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 max-w-5xl">
        <h3 class="text-lg font-semibold text-gray-800">API de integración</h3>
        <p class="text-xs text-gray-500 mt-1">Conecta tu TPV o sistema externo para sincronizar productos y precios automáticamente.</p>

        @if(! $hasApi)
            <div class="mt-5 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                La API de integración está disponible en los planes <strong>Pro</strong> y <strong>Premium</strong>.
                <a href="{{ route('subscription.manage') }}" class="ml-1 underline font-semibold">Actualizar plan →</a>
            </div>
        @else
            {{-- Endpoint base --}}
            <div class="mt-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">URL base</p>
                <code class="block bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono text-gray-700 select-all">
                    {{ rtrim(config('app.url'), '/') }}/api/v1
                </code>
            </div>

            {{-- API Key --}}
            <div class="mt-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">API Key</p>

                @if($newRawKey)
                    <div class="rounded-xl bg-green-50 border border-green-200 p-4 mb-3">
                        <p class="text-xs font-semibold text-green-700 mb-2">⚠️ Copia esta clave ahora — no volverá a mostrarse completa.</p>
                        <code class="block bg-white border border-green-300 rounded-lg px-3 py-2 text-sm font-mono text-gray-800 select-all break-all">{{ $newRawKey }}</code>
                    </div>
                @endif

                @if($activeKey)
                    <div class="flex items-center gap-3 flex-wrap">
                        <code class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono text-gray-500">{{ $activeKey->key_prefix }}</code>
                        <span class="text-xs text-gray-400">
                            Creada {{ $activeKey->created_at->diffForHumans() }}
                            @if($activeKey->last_used_at)
                                · Último uso {{ $activeKey->last_used_at->diffForHumans() }}
                            @else
                                · Nunca usada
                            @endif
                        </span>
                    </div>

                    <div class="mt-3 flex gap-2 flex-wrap">
                        <button wire:click="generate"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                            Regenerar clave
                        </button>
                        @if(! $confirmingRevoke)
                            <button wire:click="confirmRevoke"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 transition">
                                Revocar
                            </button>
                        @else
                            <span class="text-xs text-red-600 font-semibold self-center">¿Seguro? Las integraciones activas dejarán de funcionar.</span>
                            <button wire:click="revoke"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-600 hover:bg-red-700 text-white transition">
                                Sí, revocar
                            </button>
                            <button wire:click="cancelRevoke"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-600 transition">
                                Cancelar
                            </button>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-400 mb-3">No hay ninguna API key activa.</p>
                    <button wire:click="generate"
                            class="px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition">
                        Generar API key
                    </button>
                @endif
            </div>

            {{-- Referencia de endpoints --}}
            <div class="mt-8 border-t border-gray-100 pt-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Endpoints disponibles</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="pb-2 font-semibold text-gray-500 pr-4">Método</th>
                                <th class="pb-2 font-semibold text-gray-500 pr-4">Ruta</th>
                                <th class="pb-2 font-semibold text-gray-500">Descripción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr class="py-1.5">
                                <td class="py-1.5 pr-4"><span class="font-mono font-bold text-green-600">GET</span></td>
                                <td class="py-1.5 pr-4 font-mono text-gray-600">/api/v1/menu</td>
                                <td class="py-1.5 text-gray-500">Carta completa (categorías + productos)</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 pr-4"><span class="font-mono font-bold text-blue-600">POST</span></td>
                                <td class="py-1.5 pr-4 font-mono text-gray-600">/api/v1/products/sync</td>
                                <td class="py-1.5 text-gray-500">Crear o actualizar productos (por lotes)</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 pr-4"><span class="font-mono font-bold text-red-500">DELETE</span></td>
                                <td class="py-1.5 pr-4 font-mono text-gray-600">/api/v1/products/{sku}</td>
                                <td class="py-1.5 text-gray-500">Eliminar un producto</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 pr-4"><span class="font-mono font-bold text-orange-500">PATCH</span></td>
                                <td class="py-1.5 pr-4 font-mono text-gray-600">/api/v1/products/{sku}/status</td>
                                <td class="py-1.5 text-gray-500">Activar o desactivar un producto</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 pr-4"><span class="font-mono font-bold text-blue-600">POST</span></td>
                                <td class="py-1.5 pr-4 font-mono text-gray-600">/api/v1/categories/sync</td>
                                <td class="py-1.5 text-gray-500">Crear o actualizar categorías (por lotes)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-400">
                    Autenticación: <code class="bg-gray-100 px-1 rounded">Authorization: Bearer nc_live_...</code>
                    &nbsp;·&nbsp; Límite: 120 peticiones/minuto
                    &nbsp;·&nbsp; Formato: JSON
                </p>
            </div>
        @endif
    </div>
</div>
