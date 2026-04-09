<x-slot name="header">
    <h2 class="text-xl font-semibold text-gray-800">{{ __('admin.pairing_page.header') }}</h2>
</x-slot>

<div
    data-lw-close-list-root
    data-lw-close-method="closeLinkedProductsList"
    data-lw-expanded="{{ $expandedLinkedProductsPairingId ? '1' : '' }}"
>
    {{-- Bleed horizontal = padding de <main> (app layout). Cierre fuera: resources/js/app.js --}}
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8">
    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    {{-- Barra de acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
        @if(!$isOpen && $pairings->isEmpty())
            <p class="text-sm text-gray-500 max-w-xl order-2 sm:order-1">{{ __('admin.pairing_page.empty_hint') }}</p>
        @elseif(!$isOpen)
            <p class="text-sm text-gray-500 max-w-xl order-2 sm:order-1">{{ __('admin.pairing_page.ai_location_hint') }}</p>
        @else
            <div class="order-2 sm:order-1"></div>
        @endif
        <button wire:click="create()"
                class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors flex items-center gap-2 shrink-0 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('admin.pairing_page.add') }}
        </button>
    </div>

    @if($isOpen)
        <div id="lw-pairing-edit-modal-root" wire:key="pairing-edit-modal-{{ $pairing_id ?: 'new' }}" wire:click.stop>
            @include('livewire.pairingfrm')
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-lw-no-close-list wire:click.stop>
        <div class="overflow-x-auto overscroll-x-contain sm:overflow-visible">
        <table class="w-full min-w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.pairing_page.th_name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.pairing_page.th_description') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.pairing_page.th_products') }}</th>
                    <th class="w-24 px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.pairing_page.th_hide') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($pairings as $pairing)
                <tr wire:key="pairing-{{ $pairing->id }}" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <button type="button"
                                wire:click="edit({{ $pairing->id }})"
                                title="{{ __('admin.pairing_page.name_open_sheet') }}"
                                class="text-sm font-medium text-left text-gray-800 {{ $pairing->active ? 'line-through text-gray-400' : '' }} cursor-pointer hover:text-amber-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1 rounded-sm bg-transparent border-0 p-0 max-w-full">
                            {{ $pairing->name }}
                        </button>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-500 {{ $pairing->active ? 'line-through' : '' }}">
                            {{ \Illuminate\Support\Str::limit((string) ($pairing->description ?? ''), 120) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <button type="button"
                                wire:click.stop="toggleLinkedProductsList({{ $pairing->id }})"
                                style="width: fit-content;"
                                class="text-xs px-1.5 py-0.5 rounded-full border whitespace-nowrap transition-colors
                                       {{ $expandedLinkedProductsPairingId === $pairing->id
                                          ? 'bg-amber-100 border-amber-300 text-amber-700 font-semibold'
                                          : 'border-gray-200 text-gray-400 hover:border-amber-300 hover:text-amber-600' }}">
                            {{ trans_choice('admin.pairing_page.product_count', $pairing->products_count) }}
                        </button>
                    </td>
                    <td class="w-24 px-4 py-3 text-center">
                        <label class="inline-flex items-center cursor-pointer" title="{{ __('admin.pairing_page.th_hide') }}">
                            <input type="checkbox"
                                   wire:click.stop="toggleState({{ $pairing->id }})"
                                   {{ $pairing->active ? 'checked' : '' }}
                                   class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                        </label>
                    </td>
                </tr>

                @if($expandedLinkedProductsPairingId === $pairing->id)
                    <tr wire:key="pairing-linked-list-{{ $pairing->id }}">
                        <td colspan="4" class="border-t border-gray-200 admin-inset admin-inset--info px-5 py-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                                    {{ __('admin.pairing_page.linked_with', ['name' => $pairing->name]) }}
                                </p>
                                @if($expandedLinkedProducts->isEmpty())
                                    <p class="text-sm text-gray-400">{{ __('admin.pairing_page.no_linked') }}</p>
                                @else
                                    <ul class="space-y-1 max-h-60 overflow-y-auto pr-1">
                                        @foreach($expandedLinkedProducts as $p)
                                            <li class="flex items-center gap-3 py-1.5 border-b border-amber-100 last:border-0">
                                                <img src="{{ $p->photo ? asset('storage/'.$p->photo) : asset('img/noimg.png') }}"
                                                     alt=""
                                                     class="w-8 h-8 rounded-lg object-cover bg-gray-100 flex-shrink-0">
                                                <a href="{{ route('product', ['edit' => $p->id, 'from' => 'pairing']) }}"
                                                   class="text-sm text-gray-800 hover:text-amber-700 hover:underline {{ $p->active ? 'line-through text-gray-400' : '' }}">
                                                    {{ $p->name }}
                                                </a>
                                                <span class="text-xs text-gray-400">- {{ optional($p->category)->name ?? __('admin.pairing_page.uncategorized') }}</span>
                                                @if($p->offer)
                                                    <span class="text-xs bg-red-100 text-red-600 font-semibold px-1.5 py-0.5 rounded-full">{{ __('admin.pairing_page.badge_offer') }}</span>
                                                @endif
                                                <span class="ml-auto text-xs font-semibold text-gray-500 flex-shrink-0">{{ $p->price }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <button type="button" wire:click="$set('expandedLinkedProductsPairingId', null)"
                                        class="mt-3 text-xs text-gray-400 hover:text-gray-600 underline cursor-pointer">
                                    {{ __('admin.pairing_page.close_list') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="4" class="text-center py-12 text-sm text-gray-400">{{ __('admin.pairing_page.empty') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    </div>

    @if($confirmingPairingDeletion)
    <div class="fixed inset-0 z-50 flex items-center justify-center" wire:click.stop>
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="$set('confirmingPairingDeletion', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.pairing_ui.modal_delete_title') }}</h3>
            <p class="text-sm text-gray-600 mb-6">{{ __('admin.pairing_page.modal_delete_body') }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingPairingDeletion', false)" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.actions.cancel') }}
                </button>
                <button wire:click="deletePairingConfirmed" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    {{ __('admin.pairing_page.modal_confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmingPairingAiDescription)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         wire:click.stop
         wire:loading.remove
         wire:target="confirmPairingAiDescription">
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="cancelPairingAiDescription"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.pairing_page.modal_ai_title') }}</h3>
            <p class="text-sm text-gray-600 mb-2">{{ __('admin.pairing_page.modal_ai_body') }}</p>
            <p class="text-sm font-semibold text-amber-700 mb-6">
                Coste: {{ $aiPairingDescriptionCost }} créditos.
                @if($aiCredits['uses_client_key'])
                    <span class="block font-normal text-gray-600 mt-1">Se usará la API key del cliente. No se descontarán créditos de la plataforma.</span>
                @elseif($aiCredits['is_demo_unlimited'])
                    <span class="block font-normal text-gray-600 mt-1">En esta demo no se descontará saldo.</span>
                @endif
            </p>
            <div class="flex justify-end gap-3">
                <button wire:click="cancelPairingAiDescription" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.actions.cancel') }}
                </button>
                <button wire:click="confirmPairingAiDescription" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    <span wire:loading.remove wire:target="confirmPairingAiDescription">{{ __('admin.pairing_page.modal_ai_continue') }}</span>
                    <span wire:loading wire:target="confirmPairingAiDescription">{{ __('admin.pairing_page.generating_description_ai') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
