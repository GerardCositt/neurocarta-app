<div>
    @if (session()->has('message'))
        @php $isV3 = str_contains(session('message'), 'V3'); @endphp

        @if($isV3)
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
                🚀 Funcionalidad activa en la V3
            </div>
        </div>
        <script>
            setTimeout(function(){
                var el = document.getElementById('v3Overlay');
                if(el){ el.style.transition='opacity .6s'; el.style.opacity='0'; setTimeout(function(){ el.remove(); }, 700); }
            }, 3000);
        </script>
        @else
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
        @endif
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800">Pedido o lista</h3>
        <p class="text-sm text-gray-500 mt-1">Elige si el cliente hará un pedido para enviar o solo una lista para enseñarla al camarero.</p>

        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer">
                <input type="radio" wire:model="ordersMode" value="order"
                       class="mt-0.5 form-radio text-amber-500 border-gray-300 focus:ring-amber-300 cursor-pointer">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800">Pedido</div>
                    <div class="text-xs text-gray-500 mt-0.5">El cliente añade productos y puede enviar el pedido.</div>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer">
                <input type="radio" wire:model="ordersMode" value="list"
                       class="mt-0.5 form-radio text-amber-500 border-gray-300 focus:ring-amber-300 cursor-pointer">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800">Lista</div>
                    <div class="text-xs text-gray-500 mt-0.5">El cliente añade productos y enseña la lista al camarero.</div>
                </div>
            </label>
        </div>
    </div>
</div>
