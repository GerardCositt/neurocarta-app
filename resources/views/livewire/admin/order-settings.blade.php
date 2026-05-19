<div>
    @if (session()->has('message'))
        @if(session('order_settings_v3_overlay'))
        {{-- Overlay diagonal para el mensaje V3 --}}
        <div id="v3Overlay" style="
            position:fixed;inset:0;z-index:9999;
            display:flex;align-items:center;justify-content:center;
            pointer-events:none;">
            <div style="
                transform:rotate(-30deg);
                font-size:clamp(2.5rem,6vw,5rem);
                font-weight:900;
                letter-spacing:-.02em;
                white-space:nowrap;
                background:linear-gradient(135deg,#f59e0b,#d97706);
                -webkit-background-clip:text;
                -webkit-text-fill-color:transparent;
                background-clip:text;
                opacity:.85;
                text-shadow:none;
                user-select:none;">
                {{ __('admin.order_settings.v3_active') }}
            </div>
        </div>
        <script>
            setTimeout(function(){
                var el = document.getElementById('v3Overlay');
                if(el){ el.style.transition='opacity .6s'; el.style.opacity='0'; setTimeout(function(){ el.remove(); }, 700); }
            }, 2800);
        </script>
        @else
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
        @endif
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800">Pedido o lista</h3>
        <p class="text-sm text-gray-500 mt-1">{{ __('admin.order_settings.intro') }}</p>

        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors select-none
                {{ $ordersMode === 'order'
                    ? 'border-2 border-amber-400 bg-amber-50 ring-2 ring-amber-100'
                    : 'border border-gray-200 bg-white hover:bg-gray-50' }}">
                <input type="radio" wire:model="ordersMode" value="order"
                       class="mt-0.5 form-radio text-amber-600 border-gray-300 focus:ring-amber-500">
                <div class="min-w-0">
                    <div class="text-sm font-semibold {{ $ordersMode === 'order' ? 'text-amber-900' : 'text-gray-800' }}">Pedido</div>
                    <div class="text-xs {{ $ordersMode === 'order' ? 'text-amber-800/90' : 'text-gray-500' }} mt-0.5">El cliente añade productos y puede enviar el pedido.</div>
                    <p class="text-xs text-amber-700/90 mt-2 font-medium">{{ __('admin.order_settings.pedido_soon') }}</p>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors select-none
                {{ $ordersMode === 'list'
                    ? 'border-2 border-amber-300 bg-amber-50 ring-1 ring-amber-100'
                    : 'border border-gray-200 bg-white hover:bg-gray-50' }}">
                <input type="radio" wire:model="ordersMode" value="list"
                       class="mt-0.5 form-radio text-amber-500 border-gray-300 focus:ring-amber-500">
                <div class="min-w-0">
                    <div class="text-sm font-semibold {{ $ordersMode === 'list' ? 'text-amber-900' : 'text-gray-700' }}">Lista</div>
                    <div class="text-xs {{ $ordersMode === 'list' ? 'text-amber-800/80' : 'text-gray-500' }} mt-0.5">El cliente añade productos y enseña la lista al camarero.</div>
                    <p class="text-xs text-amber-700 mt-2 font-medium">{{ __('admin.order_settings.list_active') }}</p>
                </div>
            </label>
        </div>
    </div>

    <script>
        (function () {
            if (window.__ncOrdersModeRevertBound) return;
            window.__ncOrdersModeRevertBound = true;
            window.addEventListener('orders-mode-revert-list', function (e) {
                var d = e.detail || {};
                var ms = typeof d.delayMs === 'number' ? d.delayMs : 2500;
                var id = d.componentId;
                setTimeout(function () {
                    if (!window.Livewire || typeof Livewire.find !== 'function' || id == null) return;
                    var comp = Livewire.find(id);
                    if (comp) comp.call('revertOrdersModeToList');
                }, ms);
            });
        })();
    </script>
</div>
