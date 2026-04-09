<div>

    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    <div class="flex flex-col gap-3 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
            <input wire:model.debounce.400ms="q" type="search" placeholder="Buscar por nº, nombre, teléfono…"
                   class="w-full border border-gray-200 bg-white rounded-xl py-3 px-5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm" />

            <select wire:model="statusFilter"
                    class="w-full border border-gray-200 bg-white rounded-xl py-3 px-5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                <option value="">Todos los estados</option>
                @foreach($statusLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
        <table class="w-full min-w-[720px]">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Nº</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Líneas</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr wire:key="order-{{ $order->id }}" class="border-b border-gray-50 align-top">
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">#{{ $order->id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        @if($order->customer_name)
                            <div class="font-medium text-gray-800">{{ $order->customer_name }}</div>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                        @if($order->customer_phone)
                            <div class="text-xs text-gray-500 mt-0.5">{{ $order->customer_phone }}</div>
                        @endif
                        @if($order->customer_notes)
                            <p class="text-xs text-gray-500 mt-1 max-w-xs">{{ \Illuminate\Support\Str::limit($order->customer_notes, 120) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <ul class="space-y-1 max-w-md">
                            @foreach($order->items as $line)
                                <li class="flex gap-2">
                                    <span class="font-medium text-gray-700">{{ $line->quantity }}×</span>
                                    <span>{{ $line->product_name }}</span>
                                    <span class="text-gray-400">({{ $line->unit_price }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="px-4 py-3">
                        <label class="sr-only">Estado del pedido {{ $order->id }}</label>
                        <select wire:change="setStatus({{ $order->id }}, $event.target.value)"
                                class="border border-gray-200 bg-white rounded-lg py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 w-full sm:max-w-[14rem]"
                                @if($order->status === \App\Models\Order::STATUS_CANCELLED) disabled @endif>
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" @if($order->status === $key) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-sm text-gray-400">
                        No hay pedidos todavía.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        @if($orders instanceof \Illuminate\Pagination\LengthAwarePaginator && $orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
