<div>
    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div id="ai-billing" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 scroll-mt-24">
            <h3 class="text-lg font-semibold text-gray-800">IA y facturación</h3>
            <p class="text-sm text-gray-500 mt-1">Aquí se indica claramente quién paga el uso de OpenAI en este restaurante.</p>

            <div class="mt-4 rounded-xl border {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'border-sky-200 bg-sky-50' : 'border-amber-200 bg-amber-50' }} px-4 py-4">
                <p class="text-sm font-semibold {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'text-sky-900' : 'text-amber-900' }}">
                    @if($aiCredits['uses_client_key'])
                        Quien paga la IA: el cliente
                    @elseif($aiCredits['is_demo_unlimited'])
                        Quien paga la IA: demo interna
                    @else
                        Quien paga la IA: la plataforma
                    @endif
                </p>

                <p class="mt-2 text-xs {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'text-sky-700' : 'text-amber-700' }}">
                    @if($aiCredits['uses_client_key'])
                        Este restaurante tiene una API key propia configurada. La app usa esa key del cliente y no descuenta créditos de la plataforma.
                    @elseif($aiCredits['is_demo_unlimited'])
                        Esta demo está en modo ilimitado. Puedes probar imágenes, descripciones, alérgenos e importación sin consumo de saldo.
                    @else
                        Este restaurante está usando la IA gestionada por la plataforma. Por eso sí se muestran costes y sí se descuentan créditos cuando se ejecutan acciones IA.
                    @endif
                </p>

                <div class="mt-3 text-xs font-medium {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'text-sky-800' : 'text-amber-800' }}">
                    Estado visible: {{ $aiCredits['label'] }}
                </div>
            </div>
        </div>

        <div id="appearance" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 scroll-mt-24">
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

        <div id="orders" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 scroll-mt-24">
            <h3 class="text-lg font-semibold text-gray-800">Pedidos</h3>
            <p class="text-sm text-gray-500 mt-1">Activa o desactiva la posibilidad de hacer pedidos desde la carta pública.</p>

            <label class="mt-5 flex items-center justify-between gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800">Pedidos habilitados</div>
                    <div class="text-xs text-gray-500 mt-0.5">Si lo desactivas, desaparece el carrito y el envío de pedidos.</div>
                </div>
                <input type="checkbox" wire:model="ordersEnabled"
                       class="form-checkbox w-5 h-5 rounded text-amber-500 border-gray-300 focus:ring-amber-300 cursor-pointer">
            </label>

            <div class="mt-5">
                <a href="{{ url('/orders') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 border border-gray-200 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Abrir lista de pedidos
                </a>
            </div>
        </div>
    </div>
</div>
